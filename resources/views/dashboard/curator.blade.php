@extends('layouts.app')

@section('title', 'Панель куратора')

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
                            <i class="bi bi-people"></i> Пользователи
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="bi bi-people-fill"></i> Группы
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="bi bi-book"></i> Курсы
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="bi bi-calendar-check"></i> Назначения
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Панель куратора</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <span class="text-muted">{{ Auth::user()->name }}</span>
                </div>
            </div>

            @if($organization)
            <div class="alert alert-info">
                <i class="bi bi-building"></i> Организация: <strong>{{ $organization->name }}</strong>
            </div>
            @endif

            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card card-stat shadow-sm">
                        <div class="card-body">
                            <h6 class="text-muted">Пользователей в организации</h6>
                            <h2 class="mb-0">{{ $stats['users_count'] }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card card-stat shadow-sm" style="border-left-color: #28a745;">
                        <div class="card-body">
                            <h6 class="text-muted">Доступно курсов</h6>
                            <h2 class="mb-0">{{ $stats['courses_count'] }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card card-stat shadow-sm" style="border-left-color: #17a2b8;">
                        <div class="card-body">
                            <h6 class="text-muted">Групп</h6>
                            <h2 class="mb-0">{{ $stats['groups_count'] }}</h2>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
@endsection
