<?php

namespace App\Services;

use HTMLPurifier;
use HTMLPurifier_Config;
use Illuminate\Support\Facades\File;

class LearningHtml
{
    public function clean(?string $html): string
    {
        $html = trim((string) $html);
        if ($html === '') return '';

        // Older LoLeS lessons were plain text. Preserve their line breaks.
        if (!preg_match('/<\s*[a-z][a-z0-9]*\b/i', $html)) {
            return nl2br(e($html));
        }

        $config = HTMLPurifier_Config::createDefault();
        $config->set('Core.Encoding', 'UTF-8');

        // HTMLPurifier defaults to a cache directory inside vendor/, which
        // is read-only for Apache's www-data user in Docker. Laravel storage
        // is writable, persisted by the stack, and not publicly served.
        $cachePath = storage_path('framework/cache/htmlpurifier');
        File::ensureDirectoryExists($cachePath);
        $config->set('Cache.SerializerPath', $cachePath);
        $config->set('HTML.Allowed', implode(',', [
            'p', 'br', 'strong', 'em', 'u', 's', 'sub', 'sup',
            'h2', 'h3', 'h4', 'blockquote', 'ul', 'ol', 'li',
            'a[href|title|target]', 'img[src|alt|title|width|height]', 'table', 'thead', 'tbody', 'tr',
            'td[colspan|rowspan]', 'th[colspan|rowspan]', 'code', 'pre',
        ]));
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);
        $config->set('Attr.AllowedFrameTargets', ['_blank']);
        $config->set('HTML.Nofollow', true);
        return (new HTMLPurifier($config))->purify($html);
    }
}
