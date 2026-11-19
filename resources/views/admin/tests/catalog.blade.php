@extends('layouts.app')
@section('title', 'Каталог тестов')
@section('content')
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
            <div class="position-sticky pt-3">@include('partials._sidebar')</div>
        </nav>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between flex-wrap align-items-center gap-2 pt-3 pb-3 mb-3 border-bottom">
                <div>
                    <h1 class="h2 mb-1">Каталог тестов</h1>
                    <p class="text-secondary mb-0">Все проверочные задания учебного центра в одном месте</p>
                </div>
                @can('create', App\Models\Test::class)
                    <a href="{{ route('admin.courses.index') }}" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-1"></i> Выбрать курс для нового теста
                    </a>
                @endcan
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6"><div class="manager-summary h-100">
                    <div class="manager-summary__value">{{ number_format($totalTests, 0, ',', ' ') }}</div>
                    <div class="manager-summary__label">Всего тестов</div>
                </div></div>
                <div class="col-md-6"><div class="manager-summary h-100">
                    <div class="manager-summary__value">{{ number_format($activeTests, 0, ',', ' ') }}</div>
                    <div class="manager-summary__label">Активных тестов</div>
                </div></div>
            </div>

            <form method="GET" action="{{ route('admin.tests.index') }}" class="manager-toolbar mb-3" aria-label="Фильтры тестов">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-4">
                        <label for="testSearch" class="form-label">Название теста</label>
                        <input id="testSearch" type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Найти тест…">
                    </div>
                    <div class="col-lg-3">
                        <label for="testCourse" class="form-label">Курс</label>
                        <select name="course_id" id="testCourse" class="form-select">
                            <option value="">Все курсы</option>
                            @foreach($courses as $id => $title)
                                <option value="{{ $id }}" @selected((string) request('course_id') === (string) $id)>{{ $title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2">
                        <label for="testStatus" class="form-label">Статус</label>
                        <select id="testStatus" name="status" class="form-select">
                            <option value="">Любой</option>
                            <option value="active" @selected(request('status') === 'active')>Активен</option>
                            <option value="inactive" @selected(request('status') === 'inactive')>Неактивен</option>
                        </select>
                    </div>
                    <div class="col-lg-3 d-flex gap-2">
                        <button class="btn btn-primary flex-grow-1" type="submit"><i class="bi bi-search"></i> Найти</button>
                        <a class="btn btn-outline-secondary" href="{{ route('admin.tests.index') }}" title="Сбросить фильтры" aria-label="Сбросить фильтры"><i class="bi bi-x-lg"></i></a>
                    </div>
                </div>
            </form>

            <p class="small text-secondary">Найдено: {{ $tests->total() }}</p>
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover manager-table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Тест</th>
                                    <th>Курс</th>
                                    <th>Вопросов</th>
                                    <th>Проходной балл</th>
                                    <th>Статус</th>
                                    <th class="text-end pe-3">Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tests as $test)
                                <tr>
                                    <td class="ps-3">
                                        <a href="{{ route('admin.courses.tests.show', [$test->course, $test]) }}" class="fw-semibold text-decoration-none">{{ $test->title }}</a>
                                    </td>
                                    <td><a href="{{ route('admin.courses.show', $test->course) }}" class="text-decoration-none">{{ $test->course->title }}</a></td>
                                    <td>{{ $test->questions_count }}</td>
                                    <td>{{ $test->passing_score }}%</td>
                                    <td><span class="badge {{ $test->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $test->is_active ? 'Активен' : 'Неактивен' }}</span></td>
                                    <td class="text-end pe-3">
                                        <div class="btn-group btn-group-sm" role="group" aria-label="Действия для {{ $test->title }}">
                                            <a href="{{ route('admin.courses.tests.show', [$test->course, $test]) }}" class="btn btn-outline-primary" title="Просмотр" aria-label="Просмотреть {{ $test->title }}"><i class="bi bi-eye"></i></a>
                                            @can('update', $test)
                                                <a href="{{ route('admin.courses.tests.edit', [$test->course, $test]) }}" class="btn btn-outline-secondary" title="Редактировать" aria-label="Редактировать {{ $test->title }}"><i class="bi bi-pencil"></i></a>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="6"><div class="manager-empty">
                                    <i class="bi bi-file-earmark-text fs-2 d-block mb-2"></i>
                                    <strong>Тестов пока нет</strong>
                                    <p class="mb-2">Добавьте тест в существующий курс или измените параметры поиска.</p>
                                    <a href="{{ route('admin.courses.index') }}">Перейти к курсам</a>
                                </div></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="mt-3">{{ $tests->links() }}</div>
        </main>
    </div>
</div>
@endsection
