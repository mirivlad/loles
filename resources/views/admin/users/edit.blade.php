@extends('layouts.app')
@section('title', 'Редактировать пользователя')
@section('content')
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block sidebar"><div class="position-sticky pt-3">@include('partials._sidebar')</div></nav>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Редактировать: {{ $user->name }}</h1>
                <a href="{{ route('admin.users.show', $user) }}" class="btn btn-secondary btn-sm">Назад</a>
            </div>
            <form action="{{ route('admin.users.update', $user) }}" method="POST">
                @csrf @method('PUT')
                <div class="row">
                    <div class="col-md-6">
                        <div class="card shadow-sm mb-3">
                            <div class="card-body">
                                <h5>Основная информация</h5>
                                <div class="mb-3">
                                    <label class="form-label">Имя *</label>
                                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required>
                                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email *</label>
                                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required>
                                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Новый пароль</label>
                                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror">
                                    <small class="text-muted">Оставьте пустым чтобы не менять</small>
                                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Подтверждение</label>
                                    <input type="password" name="password_confirmation" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card shadow-sm mb-3">
                            <div class="card-body">
                                <h5>Настройки</h5>
                                <div class="mb-3">
                                    <label class="form-label">Организация</label>
                                    <select name="organization_id" class="form-select">
                                        <option value="">Без организации</option>
                                        @foreach($organizations as $id => $name)
                                        <option value="{{ $id }}" {{ old('organization_id', $user->organization_id) == $id ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Роль *</label>
                                    <select name="role" class="form-select">
                                        @foreach($roles as $role)
                                        <option value="{{ $role }}" {{ old('role', $user->getRoleNames()->first()) == $role ? 'selected' : '' }}>{{ $role }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @if($allGroups->count() > 0)
                                <div class="mb-3">
                                    <label class="form-label">Группы</label>
                                    <x-searchable-select
                                        name="groups[]"
                                        url="{{ route('api.groups.search') }}"
                                        placeholder="Начните вводить название группы..."
                                        :value="$userGroups"
                                        :multiple="true"
                                    />
                                    <small class="text-muted">Выберите группы</small>
                                </div>
                                @endif
                                <div class="form-check mb-3">
                                    <input type="checkbox" name="is_active" value="1" class="form-check-input" {{ old('is_active', $user->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label">Активен</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Сохранить</button>
                <a href="{{ route('admin.users.show', $user) }}" class="btn btn-secondary">Отмена</a>
            </form>
        </main>
    </div>
</div>
@endsection
