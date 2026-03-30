@extends('layouts.app')
@section('title', 'Добавить назначение')
@section('content')
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block sidebar"><div class="position-sticky pt-3">@include('partials._sidebar')</div></nav>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Добавить назначение</h1>
                <a href="{{ route('admin.course-assignments.index') }}" class="btn btn-secondary btn-sm">Назад</a>
            </div>
            
            <form action="{{ route('admin.course-assignments.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-8 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-primary text-white"><h5 class="mb-0">Основная информация</h5></div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Курс *</label>
                                    <select name="course_id" class="form-select @error('course_id') is-invalid @enderror" required>
                                        <option value="">Выберите курс</option>
                                        @foreach($courses as $id => $title)
                                        <option value="{{ $id }}" {{ old('course_id') == $id ? 'selected' : '' }}>{{ $title }}</option>
                                        @endforeach
                                    </select>
                                    @error('course_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Пользователи</label>
                                    <x-tags-input
                                        name="user_ids"
                                        url="{{ route('api.users.search') }}"
                                        placeholder="Начните вводить имя пользователя..."
                                        badge_color="success"
                                    />
                                    <small class="text-muted">Зелёные бейджи — индивидуальные назначения</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Группы</label>
                                    <x-tags-input
                                        name="group_ids"
                                        url="{{ route('api.groups.search') }}"
                                        placeholder="Начните вводить название группы..."
                                        badge_color="info"
                                    />
                                    <small class="text-muted">Голубые бейджи — назначения группам</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Организации</label>
                                    <x-tags-input
                                        name="organization_ids"
                                        url="{{ route('api.organizations.search') }}"
                                        placeholder="Начните вводить название организации..."
                                        badge_color="primary"
                                    />
                                    <small class="text-muted">Синие бейджи — назначения организациям</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Заметка</label>
                                    <textarea name="note" class="form-control" rows="3">{{ old('note') }}</textarea>
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
                                    <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date', date('Y-m-d')) }}" required>
                                    @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Дата окончания</label>
                                    <input type="date" name="end_date" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date') }}">
                                    <small class="text-muted">Оставьте пустым для бессрочного доступа</small>
                                    @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="form-check mb-3">
                                    <input type="checkbox" name="is_active" value="1" class="form-check-input" {{ old('is_active', true) ? 'checked' : '' }}>
                                    <label class="form-check-label">Активно</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">Создать назначение</button>
                <a href="{{ route('admin.course-assignments.index') }}" class="btn btn-secondary">Отмена</a>
            </form>
        </main>
    </div>
</div>
@endsection
