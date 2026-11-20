@if($module->type === 'lesson')
    <article class="course-material-text">
        @if($module->content)
            {!! app(\App\Services\LearningHtml::class)->clean($module->content) !!}
        @else
            <p class="text-secondary">Текст материала пока не добавлен.</p>
        @endif
    </article>
@elseif($module->type === 'video')
    <div class="course-material-text">
        <p>Видеоматериал откроется в отдельной вкладке.</p>
        @if($module->video_url)
            <a href="{{ $module->video_url }}" class="btn btn-primary" target="_blank" rel="noopener noreferrer">
                <i class="bi bi-play-circle me-1"></i> Открыть видео
            </a>
        @endif
    </div>
@elseif($module->type === 'file')
    <div class="course-material-text">
        <p>Файлы этого занятия доступны для скачивания:</p>
        <div class="list-group">
            @forelse($module->attachments as $file)
                <a class="list-group-item list-group-item-action d-flex align-items-center gap-3"
                   href="{{ route('course.attachments.download', [$course, $file]) }}">
                    <i class="bi bi-file-earmark-arrow-down fs-4"></i>
                    <span>{{ $file->original_name }}</span>
                    <i class="bi bi-download ms-auto"></i>
                </a>
            @empty
                <p class="text-secondary">Пока нет прикреплённых файлов.</p>
            @endforelse
        </div>
    </div>
@elseif($module->type === 'link')
    <div class="course-material-text">
        <p>Дополнительный учебный материал размещён на внешнем сайте.</p>
        @if($module->external_url)
            <a href="{{ $module->external_url }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary">
                Открыть страницу <i class="bi bi-box-arrow-up-right ms-1"></i>
            </a>
        @endif
    </div>
@elseif($module->type === 'test')
    <div class="course-material-text">
        <div class="d-flex align-items-center gap-3 mb-3">
            <i class="bi bi-clipboard-check fs-1 text-primary"></i>
            <div>
                <h2 class="h5 mb-1">{{ $module->test?->title ?? 'Проверка знаний' }}</h2>
                <p class="text-secondary mb-0">Промежуточная или итоговая проверка в последовательности курса.</p>
            </div>
        </div>
        @if($module->test)
            <p>{{ $module->test->description }}</p>
            @if($preview ?? false)
                <p class="text-secondary small">В предпросмотре прохождение теста недоступно.</p>
            @else
                <a href="{{ route('student.tests.show', $module->test) }}" class="btn btn-primary">Открыть тест</a>
            @endif
        @else
            <p class="text-secondary">Тест пока не выбран.</p>
        @endif
    </div>
@elseif(in_array($module->type, ['scorm', 'h5p']))
    <div class="course-material-text">
        <div class="alert alert-info mb-0">
            <strong>{{ strtoupper($module->type) }}:</strong> {{ $module->coursePackage?->title ?? $module->title }}.
            Проигрыватель и учёт результатов этого формата ещё не подключены.
        </div>
    </div>
@else
    <p class="text-secondary">Содержимое этого материала не настроено.</p>
@endif
