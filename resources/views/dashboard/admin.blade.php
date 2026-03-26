@extends('layouts.app')

@section('title', 'Панель администратора')

@section('content')
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
            <div class="position-sticky pt-3">
                @include('partials._sidebar')
            </div>
        </nav>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Панель администратора</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <span class="text-muted">{{ Auth::user()->name }}</span>
                </div>
            </div>

            <div class="row">
                <div class="col-md-3 mb-4">
                    <div class="card card-stat shadow-sm">
                        <div class="card-body">
                            <h6 class="text-muted">Пользователей</h6>
                            <h2 class="mb-0">{{ $stats['users_count'] }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card card-stat shadow-sm" style="border-left-color: #28a745;">
                        <div class="card-body">
                            <h6 class="text-muted">Курсов</h6>
                            <h2 class="mb-0">{{ $stats['courses_count'] }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card card-stat shadow-sm" style="border-left-color: #17a2b8;">
                        <div class="card-body">
                            <h6 class="text-muted">Организаций</h6>
                            <h2 class="mb-0">{{ $stats['organizations_count'] }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card card-stat shadow-sm" style="border-left-color: #ffc107;">
                        <div class="card-body">
                            <h6 class="text-muted">Назначений</h6>
                            <h2 class="mb-0">{{ $stats['active_assignments'] }}</h2>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-graph-up"></i> Быстрые действия</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <a href="#" class="btn btn-outline-primary w-100">
                                        <i class="bi bi-person-plus"></i> Добавить пользователя
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="#" class="btn btn-outline-success w-100">
                                        <i class="bi bi-book"></i> Создать курс
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="#" class="btn btn-outline-info w-100">
                                        <i class="bi bi-building"></i> Добавить организацию
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="#" class="btn btn-outline-warning w-100">
                                        <i class="bi bi-file-earmark-spreadsheet"></i> Экспорт отчёта
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
@endsection
