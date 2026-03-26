@extends('layouts.app')
@section('title', 'Редактировать курс')
@section('content')
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block sidebar"><div class="position-sticky pt-3">@include('partials._sidebar')</div></nav>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Редактировать: {{ $course->title }}</h1>
                <a href="{{ route('admin.courses.show', $course) }}" class="btn btn-secondary btn-sm">Назад</a>
            </div>
            <form action="{{ route('admin.courses.update', $course) }}" method="POST" enctype="multipart/form-data">
                @csrf @method('PUT')
                <div class="row">
                    <div class="col-md-8">
                        <div class="card shadow-sm mb-3">
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Название *</label>
                                    <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $course->title) }}" required>
                                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Slug</label>
                                    <input type="text" name="slug" class="form-control" value="{{ old('slug', $course->slug) }}">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Описание</label>
                                    <textarea name="description" class="form-control" rows="3">{{ old('description', $course->description) }}</textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Цели обучения</label>
                                    <textarea name="objectives" class="form-control" rows="2">{{ old('objectives', $course->objectives) }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card shadow-sm mb-3">
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Категория</label>
                                    <select name="category_id" class="form-select">
                                        <option value="">Без категории</option>
                                        @foreach($categories as $id => $name)<option value="{{ $id }}" {{ old('category_id', $course->category_id) == $id ? 'selected' : '' }}>{{ $name }}</option>@endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Тип *</label>
                                    <select name="type" class="form-select">
                                        <option value="standard" {{ old('type', $course->type) == 'standard' ? 'selected' : '' }}>Стандартный</option>
                                        <option value="scorm" {{ old('type', $course->type) == 'scorm' ? 'selected' : '' }}>SCORM</option>
                                        <option value="h5p" {{ old('type', $course->type) == 'h5p' ? 'selected' : '' }}>H5P</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Длительность (мин)</label>
                                    <input type="number" name="duration_minutes" class="form-control" value="{{ old('duration_minutes', $course->duration_minutes) }}">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Проходной балл (%)</label>
                                    <input type="number" name="passing_score" class="form-control" value="{{ old('passing_score', $course->passing_score) }}" min="0" max="100">
                                </div>
                                <div class="form-check mb-3">
                                    <input type="checkbox" name="has_certificate" value="1" class="form-check-input" {{ old('has_certificate', $course->has_certificate) ? 'checked' : '' }}>
                                    <label class="form-check-label">Выдавать сертификат</label>
                                </div>
                                <div class="form-check mb-3">
                                    <input type="checkbox" name="is_active" value="1" class="form-check-input" {{ old('is_active', $course->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label">Активен</label>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Обложка</label>
                                    @if($course->thumbnail)
                                    <div class="mb-2">
                                        <img src="{{ asset('storage/' . $course->thumbnail) }}" alt="Current thumbnail" class="img-thumbnail" style="max-width: 200px; max-height: 150px;">
                                        <div class="form-text">Текущая обложка (400x300px)</div>
                                    </div>
                                    @endif
                                    <input type="file" name="thumbnail" class="form-control" accept="image/*">
                                    <small class="text-muted">Макс. 10MB, форматы: JPEG, PNG, WebP</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Сохранить</button>
                <a href="{{ route('admin.courses.show', $course) }}" class="btn btn-secondary">Отмена</a>
            </form>
        </main>
    </div>
</div>
@endsection
