<div class="card shadow-sm mb-3 {{ $depth ? 'ms-md-4' : '' }}">
    <div class="card-body">
        <h3 class="{{ $depth ? 'h6' : 'h5' }} mb-2">
            @switch($item->type)
                @case('section') <i class="bi bi-folder2-open text-primary me-2"></i> @break
                @case('test') <i class="bi bi-check2-square text-primary me-2"></i> @break
                @case('video') <i class="bi bi-camera-video text-primary me-2"></i> @break
                @default <i class="bi bi-file-text text-primary me-2"></i>
            @endswitch
            {{ $item->title }}
        </h3>
        @if($item->duration_minutes)
            <p class="small text-secondary mb-2">Примерно {{ $item->duration_minutes }} мин.</p>
        @endif
        @if($item->type === 'lesson' && $item->content)
            <div class="lh-lg">{!! nl2br(e(strip_tags($item->content))) !!}</div>
        @elseif($item->type === 'video' && $item->video_url)
            <a href="{{ $item->video_url }}" target="_blank" rel="noopener noreferrer">Смотреть видео <i class="bi bi-box-arrow-up-right"></i></a>
        @elseif($item->type === 'file' && $item->file_path)
            <a href="{{ asset('storage/' . $item->file_path) }}" target="_blank" rel="noopener noreferrer">Открыть материал <i class="bi bi-paperclip"></i></a>
        @elseif($item->type === 'link' && $item->external_url)
            <a href="{{ $item->external_url }}" target="_blank" rel="noopener noreferrer">Открыть страницу <i class="bi bi-box-arrow-up-right"></i></a>
        @elseif($item->type === 'test' && $item->test)
            @if($preview ?? false)
                <div class="small text-secondary">Тест: {{ $item->test->title }}. Прохождение недоступно в режиме предварительного просмотра.</div>
            @else
                <a href="{{ route('student.tests.show', $item->test) }}" class="btn btn-outline-primary btn-sm">Перейти к проверке знаний</a>
            @endif
        @elseif($item->type === 'section')
            <p class="text-secondary small mb-0">Раздел курса</p>
        @else
            <p class="text-secondary small mb-0">Содержимое этого материала пока не настроено.</p>
        @endif
    </div>
</div>
