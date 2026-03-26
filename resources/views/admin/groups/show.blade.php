@extends('layouts.app')

@section('title', $group->name)

@section('content')
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
            <div class="position-sticky pt-3">
                @include('partials._sidebar')
            </div>
        </nav>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">{{ $group->name }}</h1>
                <div class="btn-toolbar">
                    @can('update', $group)
                    <a href="{{ route('admin.organizations.groups.edit', [$organization, $group]) }}" class="btn btn-warning btn-sm me-2">
                        <i class="bi bi-pencil"></i> Редактировать
                    </a>
                    @endcan
                    <a href="{{ route('admin.organizations.show', $organization) }}" class="btn btn-secondary btn-sm">
                        <i class="bi bi-arrow-left"></i> Назад
                    </a>
                </div>
            </div>

            @if(session('success'))
            <div class="alert alert-success">
                <i class="bi bi-check-circle"></i> {{ session('success') }}
            </div>
            @endif

            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="bi bi-info-circle"></i> Информация</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm">
                                <tr>
                                    <th width="40%">Название:</th>
                                    <td>{{ $group->name }}</td>
                                </tr>
                                <tr>
                                    <th>Описание:</th>
                                    <td>{{ $group->description ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <th>Статус:</th>
                                    <td>
                                        @if($group->is_active)
                                            <span class="badge bg-success">Активна</span>
                                        @else
                                            <span class="badge bg-secondary">Не активна</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Организация:</th>
                                    <td>
                                        <a href="{{ route('admin.organizations.show', $organization) }}">{{ $organization->name }}</a>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Создана:</th>
                                    <td>{{ $group->created_at->format('d.m.Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <th>Обновлена:</th>
                                    <td>{{ $group->updated_at->format('d.m.Y H:i') }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="bi bi-people-fill"></i> Пользователи</h5>
                        </div>
                        <div class="card-body">
                            <div class="display-4 text-success mb-3">{{ $group->users->count() }}</div>
                            <p class="text-muted">пользователей в группе</p>
                            
                            @if($group->users->count() > 0)
                            <hr>
                            <ul class="list-group list-group-flush">
                                @foreach($group->users->take(10) as $user)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    {{ $user->name }}
                                    <small class="text-muted">{{ $user->email }}</small>
                                </li>
                                @endforeach
                            </ul>
                            @if($group->users->count() > 10)
                            <div class="mt-2 text-muted small">
                                + ещё {{ $group->users->count() - 10 }} пользователей
                            </div>
                            @endif
                            @else
                            <p class="text-muted mb-0">В этой группе пока нет пользователей</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-calendar-check"></i> Назначения курсов</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-0">Функционал назначений курсов будет доступен в следующем обновлении.</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
@endsection
