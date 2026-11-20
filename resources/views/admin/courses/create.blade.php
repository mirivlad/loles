@extends('layouts.app')
@php($editing = isset($course) && $course->exists)
@section('title', $editing ? 'Настроить курс' : 'Новый курс')
@section('content')
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse"><div class="position-sticky pt-3">@include('partials._sidebar')</div></nav>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between align-items-center gap-3 pt-3 pb-3 mb-4 border-bottom">
                <div>
                    <h1 class="h2 mb-1">{{ $editing ? 'Настройки курса' : 'Создать курс' }}</h1>
                    <p class="text-secondary mb-0">Сначала название и описание. Учебные материалы добавляются следующим шагом.</p>
                </div>
                <a href="{{ $editing ? route('admin.courses.show', $course) : route('admin.courses.index') }}" class="btn btn-outline-secondary">Отмена</a>
            </div>
            @if($errors->any())
                <div class="alert alert-danger" role="alert">
                    Проверьте заполнение формы:
                    <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            <form method="POST"
                  action="{{ $editing ? route('admin.courses.update', $course) : route('admin.courses.store') }}"
                  enctype="multipart/form-data">
                @csrf
                @if($editing) @method('PUT') @endif
                <div class="row g-4">
                    <div class="col-xl-8">
                        <div class="card shadow-sm">
                            <div class="card-body p-4">
                                <h2 class="h5 mb-3"><span class="badge bg-primary me-1">1</span> О чём этот курс?</h2>
                                <div class="mb-3">
                                    <label for="courseTitle" class="form-label">Название *</label>
                                    <input id="courseTitle" name="title" class="form-control form-control-lg" required maxlength="255"
                                           value="{{ old('title', $course->title ?? '') }}"
                                           placeholder="Например: Вводный инструктаж по охране труда">
                                </div>
                                <div class="mb-3">
                                    <label for="courseDescription" class="form-label">Краткое описание</label>
                                    <textarea id="courseDescription" name="description" rows="5" class="form-control"
                                              placeholder="Что узнает учащийся и зачем ему этот курс?">{{ old('description', $course->description ?? '') }}</textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="courseObjectives" class="form-label">Что учащийся будет знать после обучения?</label>
                                    <textarea id="courseObjectives" name="objectives" rows="3" class="form-control"
                                              placeholder="Например: уметь распознавать опасные ситуации">{{ old('objectives', $course->objectives ?? '') }}</textarea>
                                </div>
                                <p class="small text-secondary mb-0">
                                    Не нужно выбирать SCORM, H5P, «тип модуля» или придумывать URL. Это всё доступно в содержимом курса.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-4">
                        <div class="card shadow-sm mb-3">
                            <div class="card-body">
                                <h2 class="h5 mb-3"><span class="badge bg-primary me-1">2</span> Как показывать курс?</h2>
                                <div class="mb-3">
                                    <label class="form-label" for="courseCategory">Направление обучения</label>
                                    <select class="form-select" name="category_id" id="courseCategory">
                                        <option value="">Без направления</option>
                                        @foreach($categories as $id => $name)
                                            <option value="{{ $id }}" @selected((string) old('category_id', $course->category_id ?? '') === (string) $id)>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-check form-switch mb-2">
                                    <input type="checkbox" class="form-check-input" name="is_active" id="courseActive"
                                           value="1" @checked(old('is_active', $course->is_active ?? false))>
                                    <label class="form-check-label" for="courseActive">Доступен учащимся</label>
                                </div>
                                <p class="small text-secondary">Новый курс создаётся как черновик. Включите доступ, когда наполните его материалами.</p>
                            </div>
                        </div>
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <details>
                                    <summary class="fw-semibold">Дополнительные настройки</summary>
                                    <div class="mt-3">
                                        <label class="form-label" for="courseDuration">Примерная длительность (минут)</label>
                                        <input id="courseDuration" class="form-control mb-3" type="number" min="0" max="100000"
                                               name="duration_minutes" value="{{ old('duration_minutes', $course->duration_minutes ?? '') }}">
                                        <label class="form-label" for="coursePassing">Проходной балл (%)</label>
                                        <input id="coursePassing" class="form-control mb-3" type="number" min="0" max="100"
                                               name="passing_score" value="{{ old('passing_score', $course->passing_score ?? 70) }}">
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" name="has_certificate" id="courseCertificate"
                                                   value="1" @checked(old('has_certificate', $course->has_certificate ?? false))>
                                            <label class="form-check-label" for="courseCertificate">Предусмотреть сертификат</label>
                                        </div>
                                        <label class="form-label" for="courseThumbnail">Обложка (необязательно)</label>
                                        <input id="courseThumbnail" type="file" name="thumbnail" accept="image/jpeg,image/png,image/webp" class="form-control">
                                        @if($editing && $course->thumbnail)
                                            <img src="{{ asset('storage/' . $course->thumbnail) }}" alt="Текущая обложка" class="img-thumbnail mt-2" width="160">
                                        @endif
                                    </div>
                                </details>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="d-flex gap-2 mt-4 mb-5">
                    <button class="btn btn-primary btn-lg" type="submit">
                        <i class="bi bi-check2 me-1"></i> {{ $editing ? 'Сохранить настройки' : 'Создать и перейти к содержимому' }}
                    </button>
                    <a class="btn btn-outline-secondary btn-lg" href="{{ $editing ? route('admin.courses.show', $course) : route('admin.courses.index') }}">Отмена</a>
                </div>
            </form>
        </main>
    </div>
</div>
@endsection
