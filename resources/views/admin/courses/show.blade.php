@extends('layouts.app')
@section('title', $course->title)
@section('content')
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse"><div class="position-sticky pt-3">@include('partials._sidebar')</div></nav>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div id="course-library-root"
                 data-search-url="{{ route('admin.courses.library.search', $course) }}"></div>
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 pt-3 pb-3 mb-3 border-bottom">
                <div>
                    <a href="{{ route('admin.courses.index') }}" class="small text-secondary text-decoration-none"><i class="bi bi-arrow-left"></i> Все курсы</a>
                    <h1 class="h2 mt-1 mb-1">{{ $course->title }}</h1>
                    <p class="text-secondary mb-0">
                        {{ $course->category?->name ?? 'Без направления' }} ·
                        <span class="badge {{ $course->is_active ? 'bg-success' : 'bg-secondary' }}">
                            {{ $course->is_active ? 'Доступен учащимся' : 'Черновик' }}
                        </span>
                    </p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.courses.preview', $course) }}" class="btn btn-outline-primary">
                        <i class="bi bi-eye me-1"></i> Как увидит учащийся
                    </a>
                    @can('update', $course)
                        <a href="{{ route('admin.courses.edit', $course) }}" class="btn btn-outline-secondary">
                            <i class="bi bi-sliders me-1"></i> Настройки курса
                        </a>
                    @endcan
                </div>
            </div>

            @if($errors->any())
                <div class="alert alert-danger" role="alert">
                    Не получилось сохранить материал:
                    <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif

            <div class="row g-3 mb-4">
                <div class="col-sm-4"><div class="manager-summary">
                    <div class="manager-summary__value">{{ $course->modules->count() }}</div>
                    <div class="manager-summary__label">Учебных материалов и разделов</div>
                </div></div>
                <div class="col-sm-4"><div class="manager-summary">
                    <div class="manager-summary__value">{{ $course->tests->count() }}</div>
                    <div class="manager-summary__label">Проверок знаний</div>
                </div></div>
                <div class="col-sm-4"><div class="manager-summary">
                    <div class="manager-summary__value">{{ $course->packages->count() }}</div>
                    <div class="manager-summary__label">Импортированных пакетов</div>
                </div></div>
            </div>

            <div class="card shadow-sm mb-4" id="course-materials">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2 py-3">
                    <div>
                        <h2 class="h5 mb-1">1. Содержание курса</h2>
                        <p class="small text-secondary mb-0">Материалы идут сверху вниз в том порядке, в котором их изучает учащийся.</p>
                    </div>
                    @can('update', $course)
                        <button class="btn btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#course-add-item">
                            <i class="bi bi-plus-lg"></i> Добавить материал
                        </button>
                    @endcan
                </div>
                <div class="card-body">
                    @can('update', $course)
                        <div id="course-add-item" class="collapse mb-4">
                            <div class="border rounded-3 p-3 bg-light">
                                <h3 class="h6 mb-3">Новый материал</h3>
                                @include('admin.courses._item_form', ['item' => null])
                            </div>
                        </div>
                    @endcan

                    @forelse($course->modules->whereNull('parent_id')->sortBy('sort_order') as $item)
                        @include('admin.courses._item_row', ['item' => $item, 'depth' => 0])
                        @if($item->type === 'section')
                            @foreach($item->children->sortBy('sort_order') as $child)
                                @include('admin.courses._item_row', ['item' => $child, 'depth' => 1])
                            @endforeach
                        @endif
                    @empty
                        <div class="text-center text-secondary py-5">
                            <i class="bi bi-journal-text fs-1"></i>
                            <h3 class="h5 mt-2">Здесь пока нет материалов</h3>
                            <p class="mb-0">Начните с текста или инструкции. Разделы можно добавить позже, когда уроков станет много.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="card shadow-sm mb-4" id="course-tests">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2 py-3">
                    <div>
                        <h2 class="h5 mb-1">2. Проверка знаний</h2>
                        <p class="small text-secondary mb-0">Здесь создаются тесты, которые затем можно добавить в содержание курса.</p>
                    </div>
                    @can('update', $course)
                        <a href="{{ route('admin.courses.tests.create', $course) }}" class="btn btn-outline-primary">
                            <i class="bi bi-plus-lg"></i> Создать тест
                        </a>
                    @endcan
                </div>
                <div class="card-body">
                    @forelse($course->tests as $test)
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <div>
                                <a class="fw-semibold text-decoration-none" href="{{ route('admin.courses.tests.show', [$course, $test]) }}">{{ $test->title }}</a>
                                <div class="small text-secondary">Проходной балл {{ $test->passing_score }}%</div>
                            </div>
                            @can('update', $course)
                                <a href="{{ route('admin.courses.tests.edit', [$course, $test]) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i> Изменить</a>
                            @endcan
                        </div>
                    @empty
                        <p class="text-secondary mb-0">Проверки знаний пока нет. Можно создать её после добавления материалов.</p>
                    @endforelse
                </div>
            </div>

            <div class="alert alert-info small" id="course-package-note">
                <strong>SCORM и H5P</strong> теперь добавляются через обычную кнопку
                «Добавить материал». Можно найти пакет в общей библиотеке по названию или тегам,
                либо загрузить новый. Пакеты доступны для скачивания как оригиналы.
                Исполнение и учёт результатов в процессе разработки.
            </div>
        </main>
    </div>
</div>
@push('scripts')
<script src="{{ asset('tinymce/js/tinymce/tinymce.min.js') }}"></script>
@vite('resources/js/course-builder.js')
@endpush
@endsection
