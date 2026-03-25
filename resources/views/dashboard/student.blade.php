@extends('layouts.app')

@section('title', 'Мои курсы')

@section('content')
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="{{ route('dashboard') }}">
                            <i class="bi bi-speedometer2"></i> Панель управления
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="bi bi-book"></i> Мои курсы
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="bi bi-file-earmark-text"></i> Тесты
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="bi bi-graph-up"></i> Прогресс
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Мои курсы</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <span class="text-muted">{{ Auth::user()->name }}</span>
                </div>
            </div>

            @if($courses->count() > 0)
            <div class="row">
                @foreach($courses as $course)
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm h-100">
                        @if($course->thumbnail)
                        <img src="{{ asset('storage/' . $course->thumbnail) }}" class="card-img-top" alt="{{ $course->title }}">
                        @else
                        <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center" style="height: 200px;">
                            <i class="bi bi-book text-white" style="font-size: 4rem;"></i>
                        </div>
                        @endif
                        <div class="card-body">
                            <h5 class="card-title">{{ $course->title }}</h5>
                            <p class="card-text text-muted">{{ Str::limit($course->description, 100) }}</p>
                            <div class="progress mb-3" style="height: 10px;">
                                <div class="progress-bar" role="progressbar" style="width: {{ $progress[$course->id] ?? 0 }}%"></div>
                            </div>
                            <small class="text-muted">Прогресс: {{ $progress[$course->id] ?? 0 }}%</small>
                        </div>
                        <div class="card-footer bg-white">
                            <a href="#" class="btn btn-primary w-100">
                                <i class="bi bi-play-fill"></i> Продолжить
                            </a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> У вас пока нет назначенных курсов.
            </div>
            @endif
        </main>
    </div>
</div>
@endsection
