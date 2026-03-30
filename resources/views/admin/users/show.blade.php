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
                            <div class="mb-3">
                                <x-searchable-select
                                    name="groups[]"
                                    url="{{ route('api.groups.search') }}"
                                    placeholder="Начните вводить название группы..."
                                    :value="$user->groups->pluck('id')->toArray()"
                                    :multiple="true"
                                />
                            </div>
                            @else
                            <div class="mb-3">
                                <x-searchable-select
                                    name="groups[]"
                                    url="{{ route('api.groups.search') }}"
                                    placeholder="Начните вводить название группы..."
                                    :multiple="true"
                                />
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
@endsection
