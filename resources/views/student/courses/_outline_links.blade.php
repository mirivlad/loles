@foreach($outline as $entry)
    @if($entry['kind'] === 'section')
        <div class="course-outline-section pt-3 pb-2">
            <i class="bi bi-folder2-open me-2" aria-hidden="true"></i>{{ $entry['title'] }}
        </div>
    @else
        @php
            $target = route(($preview ?? false) ? 'admin.courses.preview.material' : 'student.courses.material',
                [$course, $entry['item']]);
            $selected = isset($module) && $module->id === $entry['item']->id;
        @endphp
        <a href="{{ $target }}"
           class="course-outline-link d-flex align-items-start gap-3 text-decoration-none {{ $selected ? 'is-current' : '' }}"
           @if($selected) aria-current="page" @endif>
            <span class="course-outline-number">
                {{ $steps->search(fn ($step) => $step['item']->id === $entry['item']->id) + 1 }}
            </span>
            <span class="flex-grow-1">
                <strong class="d-block">{{ $entry['title'] }}</strong>
                <span class="small text-secondary">
                    {{ [
                        'lesson' => 'Текст',
                        'video' => 'Видео',
                        'file' => 'Файл',
                        'link' => 'Ссылка',
                        'test' => 'Проверка знаний',
                        'h5p' => 'H5P',
                        'scorm' => 'SCORM',
                    ][$entry['item']->type] ?? 'Материал' }}
                    @if($entry['item']->duration_minutes) · {{ $entry['item']->duration_minutes }} мин. @endif
                </span>
            </span>
            <i class="bi bi-chevron-right small mt-1 text-secondary" aria-hidden="true"></i>
        </a>
    @endif
@endforeach
