@extends('layouts.app')

@section('title', 'Группы организации')

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
                <h1 class="h2">Группы организации</h1>
                <div class="btn-toolbar">
                    <a href="{{ route('admin.organizations.show', $organization) }}" class="btn btn-secondary btn-sm me-2">
                        <i class="bi bi-arrow-left"></i> К организации
                    </a>
                    @can('create', App\Models\Group::class)
                    <a href="{{ route('admin.groups.create', $organization) }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-lg"></i> Добавить группу
                    </a>
                    @endcan
                </div>
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
                                    <th>Описание</th>
                                    <th>Пользователей</th>
                                    <th>Статус</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($groups as $group)
                                <tr>
                                    <td>{{ $group->id }}</td>
                                    <td><strong>{{ $group->name }}</strong></td>
                                    <td>{{ Str::limit($group->description, 50) ?? '—' }}</td>
                                    <td>{{ $group->users_count }}</td>
                                    <td>
                                        @if($group->is_active)
                                            <span class="badge bg-success">Активна</span>
                                        @else
                                            <span class="badge bg-secondary">Не активна</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            @can('update', $group)
                                            <a href="{{ route('admin.groups.edit', $group) }}" class="btn btn-outline-warning" title="Редактировать">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            @endcan
                                            @can('delete', $group)
                                            <form action="{{ route('admin.groups.destroy', $group) }}" method="POST" class="d-inline" onsubmit="return confirm('Вы уверены?')">
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
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                        <p class="mt-2">В этой организации пока нет групп</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            @if($groups->hasPages())
            <div class="mt-3">
                {{ $groups->links() }}
            </div>
            @endif
        </main>
    </div>
</div>
@endsection
