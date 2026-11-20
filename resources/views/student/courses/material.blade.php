@extends('layouts.app')
@section('title', $module->title . ' — ' . $course->title)
@section('content')
<div class="container-fluid px-3 px-lg-5 py-3 py-lg-4">
    <main class="course-reader-shell mx-auto">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <a href="{{ route(($preview ?? false) ? 'admin.courses.preview' : 'student.courses.show', $course) }}"
               class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> К содержанию
            </a>
            <button class="btn btn-outline-primary" type="button"
                    data-bs-toggle="offcanvas" data-bs-target="#courseOutlineDrawer"
                    aria-controls="courseOutlineDrawer">
                <i class="bi bi-list-ul me-1"></i> Список материалов
            </button>
        </div>

        @if(session('success'))
            <div class="alert alert-success" role="status">{{ session('success') }}</div>
        @endif

        @if($preview ?? false)
            <div class="alert alert-info py-2" role="status">
                Предпросмотр · результаты прохождения не записываются.
            </div>
        @endif

        <div class="d-flex align-items-center flex-wrap justify-content-between gap-2 mb-2">
            <a class="text-decoration-none text-secondary small"
               href="{{ route(($preview ?? false) ? 'admin.courses.preview' : 'student.courses.show', $course) }}">
                {{ $course->title }}
            </a>
            <span class="small text-secondary">Материал {{ $index + 1 }} из {{ $steps->count() }}</span>
        </div>
        <div class="progress mb-4 course-reader-position" role="progressbar"
             aria-label="Позиция в последовательности курса"
             aria-valuemin="0" aria-valuemax="100"
             aria-valuenow="{{ round(($index + 1) * 100 / max(1, $steps->count())) }}">
            <div class="progress-bar" style="width: {{ round(($index + 1) * 100 / max(1, $steps->count())) }}%"></div>
        </div>

        <section class="card shadow-sm border-0 mb-4">
            <div class="card-body p-3 p-md-4 p-lg-5">
                <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
                    <div>
                        <div class="text-secondary small mb-2">
                            {{ [
                                'lesson' => 'Текстовый материал',
                                'video' => 'Видео',
                                'file' => 'Документы',
                                'link' => 'Ссылка',
                                'test' => 'Проверка знаний',
                                'scorm' => 'SCORM',
                                'h5p' => 'H5P',
                            ][$module->type] ?? 'Материал' }}
                            @if($module->duration_minutes) · {{ $module->duration_minutes }} мин. @endif
                        </div>
                        <h1 class="h2 mb-0">{{ $module->title }}</h1>
                    </div>
                    @if($module->attachments->isNotEmpty())
                        <button type="button" class="btn btn-outline-secondary flex-shrink-0"
                                data-bs-toggle="modal" data-bs-target="#courseAttachmentsDialog">
                            <i class="bi bi-paperclip me-1"></i>
                            <span class="d-none d-sm-inline">Прикреплённые файлы</span>
                            <span class="badge text-bg-secondary ms-1">{{ $module->attachments->count() }}</span>
                        </button>
                    @endif
                </div>
                @include('student.courses._material_body')
            </div>
        </section>

        @if(!($preview ?? false) && isset($activeStudyEnrollments) && $activeStudyEnrollments->isNotEmpty() && $module->type !== 'test')
        <section class="card border-0 shadow-sm mb-4" aria-label="Отметка изучения">
            <div class="card-body">
                <h2 class="h5">Изучение материала</h2>
                <p class="small text-muted">Просмотр страницы сам по себе не считается изучением.
                    Отметьте материал после знакомства с содержанием. Для разных наборов отметки ведутся отдельно.</p>
                @foreach($activeStudyEnrollments as $enrollment)
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                    <span>{{ $enrollment->intake->name }}</span>
                    @if($studiedEnrollmentIds->contains($enrollment->id))
                        <span class="badge bg-success">Изучено</span>
                    @else
                    <form method="POST" action="{{ route('student.trainings.progress.complete', [$enrollment, $course, $module]) }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-success btn-sm">Отметить изученным</button>
                    </form>
                    @endif
                </div>
                @endforeach
            </div>
        </section>
        @endif

        <nav class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4"
             aria-label="Переход по материалам">
            @if($previous)
                <a href="{{ route(($preview ?? false) ? 'admin.courses.preview.material' : 'student.courses.material', [$course, $previous['item']]) }}"
                   class="btn btn-outline-primary">
                    <i class="bi bi-arrow-left me-1"></i> Предыдущий
                </a>
            @else
                <span></span>
            @endif
            @if($next)
                <a href="{{ route(($preview ?? false) ? 'admin.courses.preview.material' : 'student.courses.material', [$course, $next['item']]) }}"
                   class="btn btn-primary">
                    Следующий <i class="bi bi-arrow-right ms-1"></i>
                </a>
            @else
                <a href="{{ route(($preview ?? false) ? 'admin.courses.preview' : 'student.courses.show', $course) }}"
                   class="btn btn-outline-primary">
                    Вернуться к содержанию <i class="bi bi-check2 ms-1"></i>
                </a>
            @endif
        </nav>
        <p class="small text-secondary text-center mb-3">
            Номер материала показывает место в курсе, а не подтверждённое завершение обучения.
        </p>
    </main>
</div>

<div class="offcanvas offcanvas-end" tabindex="-1" id="courseOutlineDrawer"
     aria-labelledby="courseOutlineDrawerTitle" style="--bs-offcanvas-width: min(420px, 90vw)">
    <div class="offcanvas-header border-bottom">
        <div>
            <h2 class="offcanvas-title h5" id="courseOutlineDrawerTitle">Содержание курса</h2>
            <div class="small text-secondary">{{ $course->title }}</div>
        </div>
        <button class="btn-close" type="button" data-bs-dismiss="offcanvas" aria-label="Закрыть"></button>
    </div>
    <div class="offcanvas-body">
        <a href="{{ route(($preview ?? false) ? 'admin.courses.preview' : 'student.courses.show', $course) }}"
           class="btn btn-outline-secondary w-100 mb-3">
            <i class="bi bi-house me-1"></i> Все материалы
        </a>
        <nav aria-label="Перейти к материалу">
            @include('student.courses._outline_links')
        </nav>
    </div>
</div>

@if($module->attachments->isNotEmpty())
<div class="modal fade" id="courseAttachmentsDialog" tabindex="-1" aria-labelledby="courseAttachmentsTitle">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5" id="courseAttachmentsTitle">Файлы материала</h2>
                <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body">
                <div class="list-group">
                    @foreach($module->attachments as $attachment)
                        <div class="list-group-item d-flex gap-3 align-items-center flex-wrap">
                            <i class="bi bi-{{ $attachment->isImage() ? 'image' : 'file-earmark' }} fs-4 text-secondary"></i>
                            <div class="flex-grow-1">
                                <div>{{ $attachment->original_name }}</div>
                                <div class="small text-secondary">{{ number_format($attachment->size_bytes / 1024, 1, ',', ' ') }} КБ</div>
                            </div>
                            <a href="{{ route('course.attachments.download', [$course, $attachment]) }}"
                               class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-download me-1"></i> Скачать
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endif
@endsection
