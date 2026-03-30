@extends('layouts.app')
@section('title', 'Редактировать группу')
@section('content')
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block sidebar"><div class="position-sticky pt-3">@include('partials._sidebar')</div></nav>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Редактировать группу</h1>
                <a href="{{ route('admin.groups.show', $group) }}" class="btn btn-secondary btn-sm">Назад</a>
            </div>
            
            <form action="{{ route('admin.groups.update', $group) }}" method="POST">
                @csrf @method('PUT')
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-primary text-white"><h5 class="mb-0">Основная информация</h5></div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Название группы *</label>
                                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $group->name) }}" required>
                                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Описание</label>
                                    <textarea name="description" class="form-control" rows="3">{{ old('description', $group->description) }}</textarea>
                                </div>
                                <div class="form-check mb-3">
                                    <input type="checkbox" name="is_active" value="1" class="form-check-input" {{ old('is_active', $group->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label">Активна</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-success text-white"><h5 class="mb-0">Пользователи в группе</h5></div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Добавить/удалить пользователей</label>
                                    <div style="max-height: 400px; overflow-y: auto;" class="border rounded p-2">
                                        @foreach($users as $user)
                                        <div class="form-check">
                                            <input type="checkbox" name="users[]" value="{{ $user->id }}" class="form-check-input" id="user_{{ $user->id }}" {{ $group->users->contains($user->id) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="user_{{ $user->id }}">
                                                {{ $user->name }} ({{ $user->email }})
                                            </label>
                                        </div>
                                        @endforeach
                                    </div>
                                    <small class="text-muted">Отметьте пользователей для добавления в группу</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">Сохранить</button>
                <a href="{{ route('admin.groups.show', $group) }}" class="btn btn-secondary">Отмена</a>
            </form>
        </main>
    </div>
</div>
@endsection
