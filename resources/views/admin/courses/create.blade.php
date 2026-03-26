@extends('layouts.app')
@section('title', 'Добавить курс')
@section('content')
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block sidebar"><div class="position-sticky pt-3">@include('partials._sidebar')</div></nav>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Добавить курс</h1>
                <a href="{{ route('admin.courses.index') }}" class="btn btn-secondary btn-sm">Назад</a>
            </div>
            <form action="{{ route('admin.courses.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-md-8">
                        <div class="card shadow-sm mb-3">
                            <div class="card-body">
                                <h5>Основная информация</h5>
                                <div class="mb-3">
                                    <label class="form-label">Название *</label>
                                    <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" required>
                                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Slug (URL)</label>
                                    <input type="text" name="slug" class="form-control" value="{{ old('slug') }}" placeholder="автоматически">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Описание</label>
                                    <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Цели обучения</label>
                                    <textarea name="objectives" class="form-control" rows="2">{{ old('objectives') }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card shadow-sm mb-3">
                            <div class="card-body">
                                <h5>Настройки</h5>
                                <div class="mb-3">
                                    <label class="form-label">Категория</label>
                                    <select name="category_id" class="form-select">
                                        <option value="">Без категории</option>
                                        @foreach($categories as $id => $name)<option value="{{ $id }}" {{ old('category_id') == $id ? 'selected' : '' }}>{{ $name }}</option>@endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Тип *</label>
                                    <select name="type" class="form-select">
                                        <option value="standard" {{ old('type') == 'standard' ? 'selected' : '' }}>Стандартный</option>
                                        <option value="scorm" {{ old('type') == 'scorm' ? 'selected' : '' }}>SCORM</option>
                                        <option value="h5p" {{ old('type') == 'h5p' ? 'selected' : '' }}>H5P</option>
                                    </select>
                                    @error('type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Длительность (мин)</label>
                                    <input type="number" name="duration_minutes" class="form-control" value="{{ old('duration_minutes', 60) }}">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Проходной балл (%)</label>
                                    <input type="number" name="passing_score" class="form-control" value="{{ old('passing_score', 70) }}" min="0" max="100">
                                </div>
                                <div class="form-check mb-3">
                                    <input type="checkbox" name="has_certificate" value="1" class="form-check-input" {{ old('has_certificate') ? 'checked' : '' }}>
                                    <label class="form-check-label">Выдавать сертификат</label>
                                </div>
                                <div class="form-check mb-3">
                                    <input type="checkbox" name="is_active" value="1" class="form-check-input" {{ old('is_active', true) ? 'checked' : '' }}>
                                    <label class="form-check-label">Активен</label>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Обложка</label>
                                    <input type="file" name="thumbnail" class="form-control" accept="image/*">
                                    <small class="text-muted">Мин. 400x300px, макс. 2MB, форматы: JPEG, PNG, WebP</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Создать курс</button>
                <a href="{{ route('admin.courses.index') }}" class="btn btn-secondary">Отмена</a>
            </form>
        </main>
    </div>
</div>
@endsection
