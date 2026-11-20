@php
    $kindNames = [
        'section' => 'Раздел',
        'lesson' => 'Текст',
        'video' => 'Видео',
        'file' => 'Документ',
        'link' => 'Ссылка',
        'test' => 'Тест',
        'scorm' => 'SCORM (старый)',
        'h5p' => 'H5P (старый)',
        'assignment' => 'Задание (старое)',
    ];
    $kindIcons = [
        'section' => 'folder2-open',
        'lesson' => 'file-text',
        'video' => 'camera-video',
        'file' => 'paperclip',
        'link' => 'link-45deg',
        'test' => 'check2-square',
    ];
@endphp
<div class="border rounded-3 mb-2 {{ $depth ? 'ms-md-4 bg-light' : 'bg-white' }}">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 p-3">
        <div class="d-flex align-items-center gap-3">
            <div class="text-primary"><i class="bi bi-{{ $kindIcons[$item->type] ?? 'file-earmark' }} fs-4"></i></div>
            <div>
                <div class="fw-semibold">{{ $item->title }}</div>
                <div class="small text-secondary">
                    {{ $kindNames[$item->type] ?? $item->type }}
                    @if($item->duration_minutes) · {{ $item->duration_minutes }} мин.@endif
                    @if(!$item->is_active) · Скрыто от учащихся @endif
                </div>
            </div>
        </div>
        <div class="d-flex gap-1 align-items-center">
            @can('update', $course)
                <form method="POST" action="{{ route('admin.courses.modules.move', [$course, $item]) }}">
                    @csrf <input type="hidden" name="direction" value="up">
                    <button class="btn btn-sm btn-outline-secondary" type="submit" title="Выше" aria-label="Переместить {{ $item->title }} выше"><i class="bi bi-arrow-up"></i></button>
                </form>
                <form method="POST" action="{{ route('admin.courses.modules.move', [$course, $item]) }}">
                    @csrf <input type="hidden" name="direction" value="down">
                    <button class="btn btn-sm btn-outline-secondary" type="submit" title="Ниже" aria-label="Переместить {{ $item->title }} ниже"><i class="bi bi-arrow-down"></i></button>
                </form>
                <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse"
                        data-bs-target="#edit-item-{{ $item->id }}" aria-expanded="false"
                        title="Редактировать {{ $item->title }}"><i class="bi bi-pencil"></i></button>
                <form method="POST" action="{{ route('admin.courses.modules.destroy', [$course, $item]) }}"
                      onsubmit="return confirm('Удалить материал из курса?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Удалить"><i class="bi bi-trash"></i></button>
                </form>
            @endcan
        </div>
    </div>
    @if($item->type === 'lesson' && $item->content)
        <p class="small text-secondary px-3 pb-2 mb-0">{{ Str::limit(strip_tags($item->content), 220) }}</p>
    @endif
    @if($item->type === 'video' && $item->video_url)
        <div class="px-3 pb-2 small"><a href="{{ $item->video_url }}" target="_blank" rel="noopener noreferrer">Открыть видео</a></div>
    @elseif($item->type === 'file' && $item->file_path)
        <div class="px-3 pb-2 small"><a href="{{ asset('storage/' . $item->file_path) }}" target="_blank" rel="noopener noreferrer">Открыть прикреплённый файл</a></div>
    @elseif($item->type === 'link' && $item->external_url)
        <div class="px-3 pb-2 small"><a href="{{ $item->external_url }}" target="_blank" rel="noopener noreferrer">Открыть страницу</a></div>
    @elseif($item->type === 'test' && $item->test)
        <div class="px-3 pb-2 small"><a href="{{ route('admin.courses.tests.show', [$course, $item->test]) }}">{{ $item->test->title }}</a></div>
    @endif
    @can('update', $course)
        <div class="collapse border-top p-3" id="edit-item-{{ $item->id }}">
            <h3 class="h6 mb-3">Редактировать: {{ $item->title }}</h3>
            @include('admin.courses._item_form', ['item' => $item])
        </div>
    @endcan
</div>
