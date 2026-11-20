@extends('layouts.app')
@section('title', 'Курсы')
@section('content')
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
            <div class="position-sticky pt-3">@include('partials._sidebar')</div>
        </nav>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pt-3 pb-3 mb-4 border-bottom">
                <div>
                    <h1 class="h2 mb-1">Учебные курсы</h1>
                    <p class="text-secondary mb-0">Создавайте программы обучения и добавляйте материалы без технических настроек.</p>
                </div>
                @can('create', App\Models\Course::class)
                    <a href="{{ route('admin.courses.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-1"></i> Новый курс
                    </a>
                @endcan
            </div>
            @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif

            <form method="GET" action="{{ route('admin.courses.index') }}" class="manager-toolbar mb-4">
                <div class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <label class="form-label" for="courseSearch">Поиск курса</label>
                        <input id="courseSearch" name="search" type="search" class="form-control"
                               value="{{ request('search') }}" placeholder="Введите название или тему">
                    </div>
                    <div class="col-md-4">
                        <label for="courseCategoryFilter" class="form-label">Направление обучения</label>
                        <select id="courseCategoryFilter" class="form-select" name="category_id">
                            <option value="">Все направления</option>
                            @foreach($categories as $id => $name)
                                <option value="{{ $id }}" @selected((string) request('category_id') === (string) $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">Найти</button>
                        <a href="{{ route('admin.courses.index') }}" class="btn btn-outline-secondary" title="Сбросить фильтры"><i class="bi bi-x-lg"></i></a>
                    </div>
                </div>
            </form>

            <p class="small text-secondary">Найдено курсов: {{ $courses->total() }}</p>
            <div class="row g-3 mb-4">
                @forelse($courses as $course)
                    <div class="col-lg-6 col-xl-4">
                        <div class="card h-100 shadow-sm overflow-hidden">
                            @if($course->thumbnail)
                                <img src="{{ asset('storage/' . $course->thumbnail) }}" class="card-img-top"
                                     alt="Обложка {{ $course->title }}" style="height:150px;object-fit:cover">
                            @endif
                            <div class="card-body d-flex flex-column">
                                <div class="mb-2">
                                    <span class="badge {{ $course->is_active ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $course->is_active ? 'Доступен учащимся' : 'Черновик' }}
                                    </span>
                                    @if($course->category)
                                        <span class="badge bg-light text-dark border">{{ $course->category->name }}</span>
                                    @endif
                                </div>
                                <h2 class="h5 mb-2">{{ $course->title }}</h2>
                                <p class="text-secondary small">{{ Str::limit($course->description ?: 'Описание пока не заполнено.', 120) }}</p>
                                <div class="small text-secondary mt-auto mb-3">
                                    {{ $course->modules_count }} материалов ·
                                    {{ $course->tests_count }} тестов ·
                                    {{ $course->packages_count }} пакетов
                                </div>
                                <a href="{{ route('admin.courses.show', $course) }}" class="btn btn-outline-primary">
                                    <i class="bi bi-pencil-square me-1"></i> Открыть редактор
                                </a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-center border rounded-3 py-5">
                        <i class="bi bi-journal-plus fs-1 text-secondary"></i>
                        <h2 class="h5 mt-2">Курсов пока нет</h2>
                        <p class="text-secondary">Создайте первый курс: название, материалы, проверка знаний.</p>
                        @can('create', App\Models\Course::class)
                            <a href="{{ route('admin.courses.create') }}" class="btn btn-primary">Создать первый курс</a>
                        @endcan
                    </div>
                @endforelse
            </div>
            {{ $courses->links() }}
        </main>
    </div>
</div>
@endsection
