@extends('layouts.app')
@section('title', $user->name)
@section('content')
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block sidebar"><div class="position-sticky pt-3">@include('partials._sidebar')</div></nav>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">{{ $user->name }}</h1>
                <div>
                    @can('update', $user)<a href="{{ route('admin.users.edit', $user) }}" class="btn btn-warning btn-sm me-2">Редактировать</a>@endcan
                    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary btn-sm">Назад</a>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white"><h5 class="mb-0">Информация</h5></div>
                        <div class="card-body">
                            <table class="table table-sm">
                                <tr><th>Email:</th><td>{{ $user->email }}</td></tr>
                                <tr><th>Телефон:</th><td>{{ $user->phone ?? '—' }}</td></tr>
                                <tr><th>Организация:</th><td>{{ $user->organization?->name ?? '—' }}</td></tr>
                                <tr><th>Роль:</th><td><span class="badge bg-info">{{ $user->getRoleNames()->first() }}</span></td></tr>
                                <tr><th>Статус:</th><td>@if($user->is_active)<span class="badge bg-success">Активен</span>@else<span class="badge bg-secondary">Не активен</span>@endif</td></tr>
                                <tr><th>Создан:</th><td>{{ $user->created_at->format('d.m.Y H:i') }}</td></tr>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Группы</h5>
                            @can('update', App\Models\Group::class)
                            <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#addGroupModal"><i class="bi bi-plus-lg"></i></button>
                            @endcan
                        </div>
                        <div class="card-body">
                            @if($user->groups->count() > 0)
                            <ul class="list-group list-group-flush">
                                @foreach($user->groups as $group)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <a href="{{ route('admin.groups.show', $group) }}" class="text-decoration-none">{{ $group->name }}</a>
                                        <small class="text-muted d-block">{{ $group->organization?->name ?? 'Общая группа' }}</small>
                                    </div>
                                    @can('update', $group)
                                    <form action="{{ route('admin.groups.users.remove', [$group, $user]) }}" method="POST" class="d-inline" onsubmit="return confirm('Удалить пользователя из группы?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-lg"></i></button>
                                    </form>
                                    @endcan
                                </li>
                                @endforeach
                            </ul>
                            @else
                            <p class="text-muted mb-0">Не состоит в группах</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal добавления в группу -->
<div class="modal fade" id="addGroupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.groups.users.add', $user) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Добавить в группу</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Выберите группу</label>
                        <select name="group_id" class="form-select" required>
                            <option value="">Выберите группу</option>
                            @foreach($availableGroups as $group)
                            <option value="{{ $group->id }}">{{ $group->name }} @if($group->organization_id)({{ $group->organization?->name }})@else(Общая)@endif</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">Добавить</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
