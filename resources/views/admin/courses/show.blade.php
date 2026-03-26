@extends('layouts.app')
@section('title', $course->title)
@section('content')
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block sidebar"><div class="position-sticky pt-3">@include('partials._sidebar')</div></nav>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">{{ $course->title }}</h1>
                <div>
                    @can('update', $course)<a href="{{ route('admin.courses.edit', $course) }}" class="btn btn-warning btn-sm me-2">Редактировать</a>@endcan
                    <a href="{{ route('admin.courses.index') }}" class="btn btn-secondary btn-sm">Назад</a>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm">
                        @if($course->thumbnail)<img src="{{ asset('storage/' . str_replace('_thumb.', '.', $course->thumbnail)) }}" class="card-img-top" alt="{{ $course->title }}">
                        @else<div class="card-img-top bg-secondary d-flex align-items-center justify-content-center" style="height:200px;"><i class="bi bi-book text-white" style="font-size:4rem;"></i></div>@endif
                    </div>
                </div>
                <div class="col-md-8 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <table class="table table-sm">
                                <tr><th>Категория:</th><td>{{ $course->category?->name ?? '—' }}</td></tr>
                                <tr><th>Тип:</th><td><span class="badge bg-info">{{ $course->type }}</span></td></tr>
                                <tr><th>Длительность:</th><td>{{ $course->duration_minutes ?? '—' }} мин</td></tr>
                                <tr><th>Проходной балл:</th><td>{{ $course->passing_score }}%</td></tr>
                                <tr><th>Сертификат:</th><td>@if($course->has_certificate)<span class="badge bg-success">Да</span>@else<span class="badge bg-secondary">Нет</span>@endif</td></tr>
                                <tr><th>Статус:</th><td>@if($course->is_active)<span class="badge bg-success">Активен</span>@else<span class="badge bg-secondary">Не активен</span>@endif</td></tr>
                                <tr><th>Создан:</th><td>{{ $course->created_at->format('d.m.Y H:i') }}</td></tr>
                                <tr><th>Автор:</th><td>{{ $course->creator?->name ?? '—' }}</td></tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header"><h5 class="mb-0">Описание</h5></div>
                        <div class="card-body">{{ $course->description ?? '—' }}</div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header"><h5 class="mb-0">Цели обучения</h5></div>
                        <div class="card-body">{{ $course->objectives ?? '—' }}</div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header d-flex justify-content-between"><h5 class="mb-0">Модули</h5><a href="#" class="btn btn-sm btn-primary"><i class="bi bi-plus"></i></a></div>
                        <div class="card-body">
                            @if($course->modules->count() > 0)<ul class="list-group list-group-flush">@foreach($course->modules as $module)<li class="list-group-item d-flex justify-content-between">{{ $module->title }}<small class="text-muted">{{ $module->type }}</small></li>@endforeach</ul>
                            @else<p class="text-muted mb-0">Нет модулей</p>@endif
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header d-flex justify-content-between">
                            <h5 class="mb-0">Тесты</h5>
                            <a href="{{ route('admin.courses.tests.create', $course) }}" class="btn btn-sm btn-primary"><i class="bi bi-plus"></i></a>
                        </div>
                        <div class="card-body">
                            @if($course->tests->count() > 0)
                            <ul class="list-group list-group-flush">
                                @foreach($course->tests as $test)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <a href="{{ route('admin.tests.show', $test) }}" class="text-decoration-none">{{ $test->title }}</a>
                                    <span class="badge bg-secondary">{{ $test->type }}</span>
                                </li>
                                @endforeach
                            </ul>
                            @else
                            <p class="text-muted mb-0">Нет тестов</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
@endsection
