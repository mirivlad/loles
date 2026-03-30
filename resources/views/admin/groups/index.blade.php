@extends('layouts.app')
@section('title', 'Группы пользователей')
@section('content')
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block sidebar"><div class="position-sticky pt-3">@include('partials._sidebar')</div></nav>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Группы пользователей</h1>
                @can('create', App\Models\Group::class)
                <a href="{{ route('admin.groups.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Создать группу</a>
                @endcan
            </div>
            @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
            
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <form action="{{ route('admin.groups.index') }}" method="GET" class="row g-3">
                        <div class="col-md-4">
                            <select name="organization_id" class="form-select">
                                <option value="">Все группы</option>
                                <option value="general" {{ request('organization_id') === 'general' ? 'selected' : '' }}>Общие группы</option>
                                @foreach($organizations as $id => $name)
                                <option value="{{ $id }}" {{ request('organization_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <select name="user_id" class="form-select">
                                <option value="">Все пользователи</option>
                                @foreach(\App\Models\User::pluck('name', 'id') as $id => $name)
                                <option value="{{ $id }}" {{ request('user_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i></button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Название</th>
                                    <th>Организация</th>
                                    <th>Пользователей</th>
                                    <th>Статус</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($groups as $group)
                                <tr>
                                    <td><strong>{{ $group->name }}</strong></td>
                                    <td>{{ $group->organization?->name ?? '—' }}</td>
                                    <td><span class="badge bg-info">{{ $group->users->count() }}</span></td>
                                    <td>
                                        @if($group->is_active)
                                            <span class="badge bg-success">Активна</span>
                                        @else
                                            <span class="badge bg-secondary">Не активна</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('admin.groups.show', $group) }}" class="btn btn-outline-primary" title="Просмотр"><i class="bi bi-eye"></i></a>
                                            <a href="{{ route('admin.groups.edit', $group) }}" class="btn btn-outline-warning" title="Редактировать"><i class="bi bi-pencil"></i></a>
                                            <form action="{{ route('admin.groups.destroy', $group) }}" method="POST" class="d-inline" onsubmit="return confirm('Удалить?')">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-outline-danger" title="Удалить"><i class="bi bi-trash"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-5">
                                        <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                                        <p class="mt-3">Групп пока нет</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            {{ $groups->links() }}
        </main>
    </div>
</div>
@endsection
