@extends('layouts.app')

@section('title', 'LMS - Система управления обучением')

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
                <h1 class="h2">Добро пожаловать, {{ Auth::user()->name }}!</h1>
            </div>

            <div class="row">
                <div class="col-md-3 mb-4">
                    <div class="card card-stat shadow-sm">
                        <div class="card-body">
                            <h6 class="text-muted">Курсов доступно</h6>
                            <h2 class="mb-0">0</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card card-stat shadow-sm" style="border-left-color: #28a745;">
                        <div class="card-body">
                            <h6 class="text-muted">Завершено</h6>
                            <h2 class="mb-0">0</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card card-stat shadow-sm" style="border-left-color: #ffc107;">
                        <div class="card-body">
                            <h6 class="text-muted">В процессе</h6>
                            <h2 class="mb-0">0</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card card-stat shadow-sm" style="border-left-color: #dc3545;">
                        <div class="card-body">
                            <h6 class="text-muted">Тестов пройдено</h6>
                            <h2 class="mb-0">0</h2>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-book-half"></i> Доступные курсы</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">У вас пока нет назначенных курсов.</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
@endsection
