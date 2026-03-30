@extends('layouts.app')
@section('title', $group->name)
@section('content')
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block sidebar"><div class="position-sticky pt-3">@include('partials._sidebar')</div></nav>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">{{ $group->name }}</h1>
                <div>
                    <a href="{{ route('admin.groups.edit', $group) }}" class="btn btn-warning btn-sm me-2">Редактировать</a>
                    <a href="{{ route('admin.groups.index') }}" class="btn btn-secondary btn-sm">Назад</a>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white"><h5 class="mb-0">Информация</h5></div>
                        <div class="card-body">
                            <table class="table table-sm">
                                <tr><th>Организация:</th><td>{{ $group->organization?->name ?? '—' }}</td></tr>
                                <tr><th>Описание:</th><td>{{ $group->description ?? '—' }}</td></tr>
                                <tr><th>Статус:</th><td>@if($group->is_active)<span class="badge bg-success">Активна</span>@else<span class="badge bg-secondary">Не активна</span>@endif</td></tr>
                                <tr><th>Создана:</th><td>{{ $group->created_at->format('d.m.Y') }}</td></tr>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-8 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-success text-white"><h5 class="mb-0">Пользователи в группе ({{ $group->users->count() }})</h5></div>
                        <div class="card-body">
                            @if($group->users->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Имя</th>
                                            <th>Email</th>
                                            <th>Должность</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($group->users as $user)
                                        <tr>
                                            <td>{{ $user->name }}</td>
                                            <td>{{ $user->email }}</td>
                                            <td>{{ $user->getRoleNames()->first() ?? '—' }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @else
                            <p class="text-muted mb-0">В этой группе пока нет пользователей</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
@endsection
