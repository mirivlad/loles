@extends('layouts.app')

@section('title', 'Организации')

@section('content')
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('dashboard') }}">
                            <i class="bi bi-speedometer2"></i> Панель управления
                        </a>
                    </li>
                    @can('viewAny', App\Models\Organization::class)
                    <li class="nav-item">
                        <a class="nav-link active" href="{{ route('admin.organizations.index') }}">
                            <i class="bi bi-building"></i> Организации
                        </a>
                    </li>
                    @endcan
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="bi bi-people"></i> Пользователи
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="bi bi-book"></i> Курсы
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Организации</h1>
                @can('create', App\Models\Organization::class)
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="{{ route('admin.organizations.create') }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-lg"></i> Добавить организацию
                    </a>
                </div>
                @endcan
            </div>

            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Название</th>
                                    <th>INN/KPP</th>
                                    <th>Email</th>
                                    <th>Пользователей</th>
                                    <th>Групп</th>
                                    <th>Курсов</th>
                                    <th>Статус</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($organizations as $org)
                                <tr>
                                    <td>{{ $org->id }}</td>
                                    <td>
                                        <a href="{{ route('admin.organizations.show', $org) }}" class="text-decoration-none">
                                            <strong>{{ $org->name }}</strong>
                                        </a>
                                    </td>
                                    <td>
                                        @if($org->inn)
                                            <small>{{ $org->inn }}</small>
                                            @if($org->kpp)<br><small class="text-muted">{{ $org->kpp }}</small>@endif
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>{{ $org->email ?? '—' }}</td>
                                    <td>{{ $org->users_count }}</td>
                                    <td>{{ $org->groups_count }}</td>
                                    <td>{{ $org->courses_count ?? 0 }}</td>
                                    <td>
                                        @if($org->is_active)
                                            <span class="badge bg-success">Активна</span>
                                        @else
                                            <span class="badge bg-secondary">Не активна</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('admin.organizations.show', $org) }}" class="btn btn-outline-primary" title="Просмотр">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            @can('update', $org)
                                            <a href="{{ route('admin.organizations.edit', $org) }}" class="btn btn-outline-warning" title="Редактировать">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            @endcan
                                            @can('delete', $org)
                                            <form action="{{ route('admin.organizations.destroy', $org) }}" method="POST" class="d-inline" onsubmit="return confirm('Вы уверены?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger" title="Удалить">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                        <p class="mt-2">Организаций пока нет</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            @if($organizations->hasPages())
            <div class="mt-3">
                {{ $organizations->links() }}
            </div>
            @endif
        </main>
    </div>
</div>
@endsection
