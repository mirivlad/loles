@extends('layouts.app')
@section('title', 'Редактировать тест')
@section('content')
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block sidebar"><div class="position-sticky pt-3">@include('partials._sidebar')</div></nav>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Редактировать: {{ $test->title }}</h1>
                <a href="{{ route('admin.courses.tests.show', [$course, $test]) }}" class="btn btn-secondary btn-sm">Назад</a>
            </div>
            <form action="{{ route('admin.courses.tests.update', [$course, $test]) }}" method="POST">
                @csrf @method('PUT')
                <div class="row">
                    <div class="col-md-8">
                        <div class="card shadow-sm mb-3">
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Название *</label>
                                    <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $test->title) }}" required>
                                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Описание</label>
                                    <textarea name="description" class="form-control" rows="3">{{ old('description', $test->description) }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card shadow-sm mb-3">
                            <div class="card-body">
                                <h5>Настройки</h5>
                                <div class="mb-3">
                                    <label class="form-label">Тип *</label>
                                    <select name="type" class="form-select">
                                        <option value="intermediate" {{ old('type', $test->type) == 'intermediate' ? 'selected' : '' }}>Промежуточный</option>
                                        <option value="probationary" {{ old('type', $test->type) == 'probationary' ? 'selected' : '' }}>Пробный</option>
                                        <option value="final" {{ old('type', $test->type) == 'final' ? 'selected' : '' }}>Итоговый</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Время (мин)</label>
                                    <input type="number" name="time_limit_minutes" class="form-control" value="{{ old('time_limit_minutes', $test->time_limit_minutes) }}">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Проходной балл (%)</label>
                                    <input type="number" name="passing_score" class="form-control" value="{{ old('passing_score', $test->passing_score) }}" min="0" max="100">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Попыток</label>
                                    <input type="number" name="max_attempts" class="form-control" value="{{ old('max_attempts', $test->max_attempts) }}" min="1">
                                </div>
                                <div class="form-check mb-3">
                                    <input type="checkbox" name="shuffle_questions" value="1" class="form-check-input" {{ old('shuffle_questions', $test->shuffle_questions) ? 'checked' : '' }}>
                                    <label class="form-check-label">Перемешать вопросы</label>
                                </div>
                                <div class="form-check mb-3">
                                    <input type="checkbox" name="show_correct_answers" value="1" class="form-check-input" {{ old('show_correct_answers', $test->show_correct_answers) ? 'checked' : '' }}>
                                    <label class="form-check-label">Показывать правильные ответы</label>
                                </div>
                                <div class="form-check mb-3">
                                    <input type="checkbox" name="is_active" value="1" class="form-check-input" {{ old('is_active', $test->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label">Активен</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Сохранить</button>
                <a href="{{ route('admin.courses.tests.show', [$course, $test]) }}" class="btn btn-secondary">Отмена</a>
            </form>
        </main>
    </div>
</div>
@endsection
