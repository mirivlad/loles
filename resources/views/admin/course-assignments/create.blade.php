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
                    <div class="col-md-8">
                        <div class="card shadow-sm mb-3">
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
                                    <label class="form-label">Тип назначения *</label>
                                    <select name="type" id="assignmentType" class="form-select @error('type') is-invalid @enderror" required onchange="updateAssignmentFields()">
                                        <option value="">Выберите тип</option>
                                        <option value="individual" {{ old('type') == 'individual' ? 'selected' : '' }}>Индивидуальному пользователю</option>
                                        <option value="group" {{ old('type') == 'group' ? 'selected' : '' }}>Группе</option>
                                        <option value="organization" {{ old('type') == 'organization' ? 'selected' : '' }}>Организации</option>
                                    </select>
                                    @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                
                                <div class="mb-3" id="userField" style="display:none;">
                                    <label class="form-label">Пользователь *</label>
                                    <select name="user_id" class="form-select">
                                        <option value="">Выберите пользователя</option>
                                        @foreach($users as $id => $name)
                                        <option value="{{ $id }}" {{ old('user_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                <div class="mb-3" id="groupField" style="display:none;">
                                    <label class="form-label">Группа *</label>
                                    <select name="group_id" class="form-select">
                                        <option value="">Выберите группу</option>
                                        @foreach($groups as $id => $name)
                                        <option value="{{ $id }}" {{ old('group_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                <div class="mb-3" id="organizationField" style="display:none;">
                                    <label class="form-label">Организация *</label>
                                    <select name="organization_id" class="form-select">
                                        <option value="">Выберите организацию</option>
                                        @foreach($organizations as $id => $name)
                                        <option value="{{ $id }}" {{ old('organization_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Заметка</label>
                                    <textarea name="note" class="form-control" rows="3">{{ old('note') }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card shadow-sm mb-3">
                            <div class="card-body">
                                <h5>Период доступа</h5>
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

<script>
function updateAssignmentFields() {
    const type = document.getElementById('assignmentType').value;
    document.getElementById('userField').style.display = (type === 'individual') ? 'block' : 'none';
    document.getElementById('groupField').style.display = (type === 'group') ? 'block' : 'none';
    document.getElementById('organizationField').style.display = (type === 'organization') ? 'block' : 'none';
}

// Инициализация при загрузке
document.addEventListener('DOMContentLoaded', function() {
    updateAssignmentFields();
});
</script>
@endsection
