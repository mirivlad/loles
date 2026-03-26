@extends('layouts.app')

@section('title', 'Редактировать группу')

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
                <h1 class="h2">Редактировать группу</h1>
                <a href="{{ route('admin.organizations.show', $organization) }}" class="btn btn-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Назад
                </a>
            </div>

            @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <form action="{{ route('admin.groups.update', $group) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-8">
                        <div class="card shadow-sm mb-4">
                            <div class="card-body">
                                <h5 class="card-title mb-3">Основная информация</h5>
                                
                                <div class="mb-3">
                                    <label for="name" class="form-label">Название группы <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                           id="name" name="name" value="{{ old('name', $group->name) }}" required autofocus>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Описание</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror" 
                                              id="description" name="description" rows="3">{{ old('description', $group->description) }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" {{ old('is_active', $group->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">Активна</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card shadow-sm mb-4">
                            <div class="card-body">
                                <h5 class="card-title mb-3">Организация</h5>
                                <p class="text-muted mb-0">{{ $organization->name }}</p>
                                @if($organization->inn)
                                <p class="text-muted small">ИНН: {{ $organization->inn }}</p>
                                @endif
                                <hr>
                                <p class="text-muted small mb-0">
                                    Создана: {{ $group->created_at->format('d.m.Y') }}<br>
                                    Обновлена: {{ $group->updated_at->format('d.m.Y') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg"></i> Сохранить изменения
                    </button>
                    <a href="{{ route('admin.organizations.show', $organization) }}" class="btn btn-secondary">
                        <i class="bi bi-x-lg"></i> Отмена
                    </a>
                </div>
            </form>
        </main>
    </div>
</div>
@endsection
