@extends('layouts.app')
@section('title', $course->title)
@section('content')
<div class="container-fluid px-3 px-lg-5 py-4">
    <main class="course-reader-shell mx-auto">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
            <a class="text-decoration-none" href="{{ ($preview ?? false) ? route('admin.courses.show', $course) : route('student.courses.index') }}">
                <i class="bi bi-arrow-left me-1"></i>
                {{ ($preview ?? false) ? 'Вернуться к редактору' : 'Мои курсы' }}
            </a>
            @if($preview ?? false)
                <span class="badge text-bg-info">Предпросмотр · без записи результатов</span>
            @endif
        </div>
        <header class="course-reader-hero mb-4">
            <span class="small text-secondary">{{ $course->category?->name ?? 'Учебный курс' }}</span>
            <h1 class="display-6 fw-semibold mt-2 mb-3">{{ $course->title }}</h1>
            @if($course->description)
                <p class="lead mb-3">{{ $course->description }}</p>
            @endif
            @if($course->objectives)
                <div class="text-secondary mb-3"><strong>Цель обучения:</strong> {{ $course->objectives }}</div>
            @endif
            <div class="d-flex flex-wrap align-items-center gap-3">
                <span class="text-secondary small">
                    <i class="bi bi-list-ol me-1"></i>{{ $steps->count() }} материалов
                    @if($course->duration_minutes) · примерно {{ $course->duration_minutes }} минут @endif
                </span>
                @if($steps->isNotEmpty())
                    <a class="btn btn-primary" href="{{ route(($preview ?? false) ? 'admin.courses.preview.material' : 'student.courses.material', [$course, $steps->first()['item']]) }}">
                        Начать с первого материала <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                @endif
            </div>
        </header>
        <section aria-labelledby="courseContentsHeading" class="card border-0 shadow-sm">
            <div class="card-body p-3 p-lg-4">
                <h2 id="courseContentsHeading" class="h4 mb-1">Содержание курса</h2>
                <p class="text-secondary mb-3">Выберите материал. Внутри него можно перейти к следующему или вернуться к этому списку.</p>
                @if($steps->isEmpty())
                    <div class="alert alert-light mb-0">Учебные материалы пока не добавлены.</div>
                @else
                    <nav aria-label="Материалы курса">
                        @include('student.courses._outline_links')
                    </nav>
                @endif
            </div>
        </section>
    </main>
</div>
@endsection
