<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;
use ZipArchive;

class CoursePackageInspector
{
    private const MAX_ENTRIES = 10000;
    private const MAX_UNCOMPRESSED_BYTES = 536870912; // 512 MiB
    private const MAX_MANIFEST_BYTES = 1048576;

    /**
     * Inspect, but NEVER extract or execute, a third-party learning package.
     * Returns version/title/metadata suitable for saving with its original ZIP.
     */
    public function inspect(string $path, string $format): array
    {
        if (!in_array($format, ['scorm', 'h5p'], true)) {
            throw ValidationException::withMessages(['format' => 'Выберите SCORM или H5P.']);
        }

        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CHECKCONS) !== true) {
            throw ValidationException::withMessages(['package' => 'Не удалось открыть ZIP-пакет.']);
        }

        try {
            if ($zip->numFiles < 1 || $zip->numFiles > self::MAX_ENTRIES) {
                $this->reject('В пакете слишком много файлов.');
            }

            $total = 0;
            $names = [];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = $zip->statIndex($i);
                $name = (string) $entry['name'];
                $size = (int) $entry['size'];
                if ($name === '' || strlen($name) > 1024
                    || str_contains($name, chr(92))
                    || str_contains($name, chr(0))
                    || str_starts_with($name, '/')
                    || preg_match('/^[A-Za-z]:/', $name)
                    || preg_match('~(^|/)(\.\.?)(/|$)~', $name)) {
                    $this->reject('В ZIP обнаружен небезопасный путь.');
                }
                $key = strtolower($name);
                if (isset($names[$key])) {
                    $this->reject('В ZIP есть повторяющиеся пути.');
                }
                $names[$key] = true;

                // Disallow symlink entries, even though archives remain private.
                if (method_exists($zip, 'getExternalAttributesIndex')) {
                    $opsys = 0;
                    $attributes = 0;
                    if ($zip->getExternalAttributesIndex($i, $opsys, $attributes)
                        && (($attributes >> 16) & 0170000) === 0120000) {
                        $this->reject('ZIP содержит символические ссылки.');
                    }
                }

                $total += $size;
                if ($total > self::MAX_UNCOMPRESSED_BYTES) {
                    $this->reject('Распакованные данные превышают 512 МБ.');
                }
            }

            return $format === 'scorm'
                ? $this->inspectScorm($zip, $names)
                : $this->inspectH5p($zip, $names);
        } finally {
            $zip->close();
        }
    }

    private function inspectScorm(ZipArchive $zip, array $names): array
    {
        if (!isset($names['imsmanifest.xml'])) {
            $this->reject('SCORM-пакет должен содержать imsmanifest.xml в корне архива.');
        }
        $xml = $zip->getFromName('imsmanifest.xml');
        if (!is_string($xml) || strlen($xml) > self::MAX_MANIFEST_BYTES
            || stripos($xml, '<!DOCTYPE') !== false) {
            $this->reject('Недопустимый SCORM-манифест.');
        }

        $document = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            $ok = $document->loadXML($xml, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        if (!$ok || $document->documentElement?->localName !== 'manifest') {
            $this->reject('Некорректный imsmanifest.xml.');
        }

        $xpath = new \DOMXPath($document);
        $version = trim((string) $xpath->evaluate('string((//*[local-name()="metadata"]/*[local-name()="schemaversion"])[1])'));
        $schema = trim((string) $xpath->evaluate('string((//*[local-name()="metadata"]/*[local-name()="schema"])[1])'));
        $resourceCount = (int) $xpath->evaluate('count(//*[local-name()="resources"]/*[local-name()="resource"])');

        if ($resourceCount < 1 || !$xpath->query('//*[local-name()="organizations"]')->length) {
            $this->reject('SCORM-манифест не содержит структуры обучения или ресурсов.');
        }

        if (preg_match('/^1\.2(?:\b|$)/', $version)) {
            $standardVersion = 'SCORM 1.2';
        } elseif (str_contains($version, '2004')) {
            $standardVersion = 'SCORM 2004';
        } else {
            $this->reject('Поддерживаются пакеты SCORM 1.2 и SCORM 2004.');
        }

        $title = trim((string) $xpath->evaluate('string((//*[local-name()="organizations"]/*[local-name()="organization"]/*[local-name()="title"])[1])'));

        return [
            'standard_version' => $standardVersion,
            'title' => mb_substr($title, 0, 255) ?: 'SCORM-пакет',
            'metadata' => [
                'schema' => $schema,
                'schema_version' => $version,
                'resource_count' => $resourceCount,
            ],
        ];
    }

    private function inspectH5p(ZipArchive $zip, array $names): array
    {
        if (!isset($names['h5p.json']) || !isset($names['content/content.json'])) {
            $this->reject('В H5P-пакете должны быть h5p.json и content/content.json.');
        }

        $json = $zip->getFromName('h5p.json');
        $contentJson = $zip->getFromName('content/content.json');
        if (!is_string($json) || strlen($json) > self::MAX_MANIFEST_BYTES
            || !is_string($contentJson) || strlen($contentJson) > 20 * self::MAX_MANIFEST_BYTES) {
            $this->reject('Некорректное содержимое H5P.');
        }

        try {
            $metadata = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            $content = json_decode($contentJson, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            $this->reject('В H5P обнаружен некорректный JSON.');
        }

        if (!is_array($metadata) || !is_array($content)
            || !is_string($metadata['mainLibrary'] ?? null)
            || !is_array($metadata['preloadedDependencies'] ?? null)) {
            $this->reject('В H5P отсутствует описание основного типа содержимого или библиотек.');
        }

        return [
            'standard_version' => 'H5P',
            'title' => mb_substr(strip_tags((string) ($metadata['title'] ?? 'H5P-задание')), 0, 255),
            'metadata' => [
                'main_library' => mb_substr($metadata['mainLibrary'], 0, 120),
                'dependency_count' => count($metadata['preloadedDependencies']),
            ],
        ];
    }

    private function reject(string $message): never
    {
        throw ValidationException::withMessages(['package' => $message]);
    }
}
