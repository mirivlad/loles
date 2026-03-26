@extends('layouts.app')
@section('title', 'Тесты курса')
@section('content')
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block sidebar"><div class="position-sticky pt-3">@include('partials._sidebar')</div></nav>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Тесты: {{ $course->title }}</h1>
                <a href="{{ route('admin.courses.tests.create', $course) }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Добавить тест</a>
            </div>
            @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
            
            <div class="mb-3"><a href="{{ route('admin.courses.show', $course) }}" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> Назад к курсу</a></div>
            
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="row">
                        @forelse($tests as $test)
                        <div class="col-md-6 mb-4">
                            <div class="card h-100 shadow-sm">
                                <div class="card-body">
                                    <h5 class="card-title">{{ $test->title }}</h5>
                                    <p class="card-text text-muted small">{{ Str::limit($test->description, 80) }}</p>
                                    <div class="mb-2">
                                        <span class="badge bg-info">{{ $test->type }}</span>
                                        <span class="badge bg-secondary">{{ $test->questions_count }} вопросов</span>
                                        @if($test->time_limit_minutes)<span class="badge bg-warning">{{ $test->time_limit_minutes }} мин</span>@endif
                                    </div>
                                    <small class="text-muted">Проходной балл: {{ $test->passing_score }}%</small>
                                </div>
                                <div class="card-footer bg-white">
                                    <div class="btn-group btn-group-sm w-100">
                                        <a href="{{ route('admin.tests.show', $test) }}" class="btn btn-outline-primary"><i class="bi bi-eye"></i></a>
                                        <a href="{{ route('admin.tests.edit', $test) }}" class="btn btn-outline-warning"><i class="bi bi-pencil"></i></a>
                                        <form action="{{ route('admin.tests.destroy', $test) }}" method="POST" class="d-inline" onsubmit="return confirm('Удалить?')">@csrf @method('DELETE')<button class="btn btn-outline-danger"><i class="bi bi-trash"></i></button></form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="col-12 text-center text-muted py-5"><i class="bi bi-inbox" style="font-size:3rem;"></i><p class="mt-3">Тестов пока нет</p></div>
                        @endforelse
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
@endsection
