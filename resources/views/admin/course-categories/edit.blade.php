@extends('layouts.app')
@section('title', 'Редактировать категорию')
@section('content')
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block sidebar"><div class="position-sticky pt-3">@include('partials._sidebar')</div></nav>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Редактировать: {{ $category->name }}</h1>
                <a href="{{ route('admin.course-categories.show', $category) }}" class="btn btn-secondary btn-sm">Назад</a>
            </div>
            <form action="{{ route('admin.course-categories.update', $category) }}" method="POST">
                @csrf @method('PUT')
                <div class="row">
                    <div class="col-md-8">
                        <div class="card shadow-sm mb-3">
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Название *</label>
                                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $category->name) }}" required>
                                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Slug (URL)</label>
                                    <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug', $category->slug) }}">
                                    @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Описание</label>
                                    <textarea name="description" class="form-control" rows="3">{{ old('description', $category->description) }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card shadow-sm mb-3">
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Родительская категория</label>
                                    <select name="parent_id" class="form-select">
                                        <option value="">Без родителя</option>
                                        @foreach($parentCategories as $cat)
                                        <option value="{{ $cat->id }}" {{ old('parent_id', $category->parent_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Порядок</label>
                                    <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $category->sort_order) }}">
                                </div>
                                <div class="form-check mb-3">
                                    <input type="checkbox" name="is_active" value="1" class="form-check-input" {{ old('is_active', $category->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label">Активна</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Сохранить</button>
                <a href="{{ route('admin.course-categories.show', $category) }}" class="btn btn-secondary">Отмена</a>
            </form>
        </main>
    </div>
</div>
@endsection
