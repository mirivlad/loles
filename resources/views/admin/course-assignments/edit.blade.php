@extends('layouts.app')
@section('title', 'Редактировать назначение')
@section('content')
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block sidebar"><div class="position-sticky pt-3">@include('partials._sidebar')</div></nav>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Редактировать назначение</h1>
                <a href="{{ route('admin.course-assignments.show', $assignment) }}" class="btn btn-secondary btn-sm">Назад</a>
            </div>
            
            <form action="{{ route('admin.course-assignments.update', $assignment) }}" method="POST">
                @csrf @method('PUT')
                <div class="row">
                    <div class="col-md-8 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-primary text-white"><h5 class="mb-0">Основная информация</h5></div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Курс</label>
                                    <input type="text" class="form-control" value="{{ $assignment->course->title }}" disabled>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Тип</label>
                                    <input type="text" class="form-control" value="@if($assignment->type === 'individual')Индивидуально@elseif($assignment->type === 'group')Группе@elseОрганизации@endif" disabled>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Назначено</label>
                                    <input type="text" class="form-control" value="@if($assignment->type === 'individual'){{ $assignment->user?->name }}@elseif($assignment->type === 'group'){{ $assignment->group?->name }}@else{{ $assignment->organization?->name }}@endif" disabled>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Заметка</label>
                                    <textarea name="note" class="form-control" rows="3">{{ old('note', $assignment->note) }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-success text-white"><h5 class="mb-0">Период доступа</h5></div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Дата начала *</label>
                                    <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date', $assignment->start_date->format('Y-m-d')) }}" required>
                                    @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Дата окончания</label>
                                    <input type="date" name="end_date" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date', $assignment->end_date?->format('Y-m-d')) }}">
                                    <small class="text-muted">Оставьте пустым для бессрочного доступа</small>
                                    @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="form-check mb-3">
                                    <input type="checkbox" name="is_active" value="1" class="form-check-input" {{ old('is_active', $assignment->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label">Активно</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">Сохранить</button>
                <a href="{{ route('admin.course-assignments.show', $assignment) }}" class="btn btn-secondary">Отмена</a>
            </form>
        </main>
    </div>
</div>
@endsection
