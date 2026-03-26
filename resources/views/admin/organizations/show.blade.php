@extends('layouts.app')

@section('title', $organization->name)

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
                <h1 class="h2">{{ $organization->name }}</h1>
                <div class="btn-toolbar">
                    @can('update', $organization)
                    <a href="{{ route('admin.organizations.edit', $organization) }}" class="btn btn-warning btn-sm me-2">
                        <i class="bi bi-pencil"></i> Редактировать
                    </a>
                    @endcan
                    <a href="{{ route('admin.organizations.index') }}" class="btn btn-secondary btn-sm">
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
                                    <td>{{ $organization->name }}</td>
                                </tr>
                                <tr>
                                    <th>ИНН/КПП:</th>
                                    <td>{{ $organization->inn ?? '—' }} / {{ $organization->kpp ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <th>Адрес:</th>
                                    <td>{{ $organization->address ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <th>Email:</th>
                                    <td>{{ $organization->email ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <th>Телефон:</th>
                                    <td>{{ $organization->phone ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <th>Статус:</th>
                                    <td>
                                        @if($organization->is_active)
                                            <span class="badge bg-success">Активна</span>
                                        @else
                                            <span class="badge bg-secondary">Не активна</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Описание:</th>
                                    <td>{{ $organization->description ?? '—' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="bi bi-graph-up"></i> Статистика</h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-4 mb-3">
                                    <div class="display-4 text-primary">{{ $organization->users->count() }}</div>
                                    <div class="text-muted">Пользователей</div>
                                </div>
                                <div class="col-4 mb-3">
                                    <div class="display-4 text-success">{{ $organization->groups->count() }}</div>
                                    <div class="text-muted">Групп</div>
                                </div>
                                <div class="col-4 mb-3">
                                    <div class="display-4 text-info">{{ $organization->courseRequests->count() }}</div>
                                    <div class="text-muted">Заявок</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-people"></i> Пользователи</h5>
                        </div>
                        <div class="card-body">
                            @if($organization->users->count() > 0)
                            <ul class="list-group list-group-flush">
                                @foreach($organization->users->take(5) as $user)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    {{ $user->name }}
                                    <small class="text-muted">{{ $user->email }}</small>
                                </li>
                                @endforeach
                            </ul>
                            @if($organization->users->count() > 5)
                            <div class="mt-2 text-muted small">
                                + ещё {{ $organization->users->count() - 5 }} пользователей
                            </div>
                            @endif
                            @else
                            <p class="text-muted mb-0">Нет пользователей</p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bi bi-people-fill"></i> Группы</h5>
                            @can('create', App\Models\Group::class)
                            <a href="{{ route('admin.organizations.groups.create', $organization) }}" class="btn btn-sm btn-primary">
                                <i class="bi bi-plus-lg"></i>
                            </a>
                            @endcan
                        </div>
                        <div class="card-body">
                            @if($organization->groups->count() > 0)
                            <ul class="list-group list-group-flush">
                                @foreach($organization->groups->take(5) as $group)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>{{ $group->name }}</strong>
                                        @if($group->description)
                                        <br><small class="text-muted">{{ Str::limit($group->description, 50) }}</small>
                                        @endif
                                    </div>
                                    <div class="btn-group btn-group-sm">
                                        <a href="#" class="btn btn-outline-primary" title="Просмотр">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @can('update', $group)
                                        <a href="{{ route('admin.organizations.groups.edit', [$organization, $group]) }}" class="btn btn-outline-warning" title="Редактировать">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        @endcan
                                    </div>
                                </li>
                                @endforeach
                            </ul>
                            @if($organization->groups->count() > 5)
                            <div class="mt-2 text-muted small">
                                + ещё {{ $organization->groups->count() - 5 }} групп
                            </div>
                            @endif
                            @else
                            <p class="text-muted mb-0">Нет групп</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
@endsection
