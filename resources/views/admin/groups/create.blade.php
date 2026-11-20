@extends('layouts.app')
@section('title', 'Создать группу')
@section('content')
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block sidebar"><div class="position-sticky pt-3">@include('partials._sidebar')</div></nav>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Создать группу</h1>
                <a href="{{ route('admin.groups.index') }}" class="btn btn-secondary btn-sm">Назад</a>
            </div>
            
            <form action="{{ route('admin.groups.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-8 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-primary text-white"><h5 class="mb-0">Основная информация</h5></div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Тип группы *</label>
                                    <select name="group_type" id="groupType" class="form-select @error('group_type') is-invalid @enderror" required onchange="toggleOrganizationField()">
                                        <option value="organization" {{ old('group_type', request('organization_id') ? 'organization' : '') == 'organization' ? 'selected' : '' }}>Группа организации</option>
                                        <option value="general" {{ old('group_type', request('organization_id') ? '' : 'general') == 'general' ? 'selected' : '' }}>Общая группа</option>
                                    </select>
                                    <small class="text-muted">Группа организации — только пользователи этой организации. Общая — любые пользователи.</small>
                                    @error('group_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                
                                <div class="mb-3" id="organizationField" style="display:{{ request('organization_id') || old('group_type') == 'organization' ? 'block' : 'none' }};">
                                    <label class="form-label">Предприятие *</label>
                                    <x-searchable-select
                                        name="organization_id"
                                        url="{{ route('api.organizations.search') }}"
                                        placeholder="Начните вводить название предприятия..."
                                        :value="request('organization_id') ?: old('organization_id')"
                                        :required="true"
                                    />
                                    @error('organization_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Название группы *</label>
                                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Описание</label>
                                    <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="groupStatus" class="form-label">Состояние группы</label>
                                    <select id="groupStatus" name="status" class="form-select">
                                        <option value="planned" @selected(old('status') === 'planned')>Запланирована</option>
                                        <option value="active" @selected(old('status', 'active') === 'active')>Обучается</option>
                                        <option value="completed" @selected(old('status') === 'completed')>Завершена</option>
                                        <option value="archived" @selected(old('status') === 'archived')>В архиве</option>
                                    </select>
                                    <div class="form-text">Завершение сохраняет состав, историю назначений и результаты обучения.</div>
                                </div>
                                <div class="row g-2 mb-3">
                                    <div class="col-md-6">
                                        <label for="groupStartDate" class="form-label">Начало обучения</label>
                                        <input id="groupStartDate" type="date" name="started_on" class="form-control" value="{{ old('started_on') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="groupFinishDate" class="form-label">Завершение обучения</label>
                                        <input id="groupFinishDate" type="date" name="finished_on" class="form-control" value="{{ old('finished_on') }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-info text-white"><h5 class="mb-0">Подсказка</h5></div>
                            <div class="card-body">
                                <h6>Группа организации</h6>
                                <p class="small text-muted">Привязана к конкретной организации. Можно добавлять только пользователей этой организации.</p>
                                <hr>
                                <h6>Общая группа</h6>
                                <p class="small text-muted">Не привязана к организации. Можно добавлять любых пользователей системы.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">Создать группу</button>
                <a href="{{ route('admin.groups.index') }}" class="btn btn-secondary">Отмена</a>
            </form>
        </main>
    </div>
</div>

<script>
function toggleOrganizationField() {
    const type = document.getElementById('groupType').value;
    const orgField = document.getElementById('organizationField');
    const orgSelect = orgField.querySelector('select');
    const orgHidden = document.getElementById('organization_id');
    
    if (type === 'organization') {
        orgField.style.display = 'block';
        orgSelect.setAttribute('required', 'required');
        if (orgHidden) orgHidden.setAttribute('required', 'required');
    } else {
        orgField.style.display = 'none';
        orgSelect.removeAttribute('required');
        if (orgHidden) orgHidden.removeAttribute('required');
        orgSelect.value = ''; // Очищаем значение
        if (orgHidden) orgHidden.value = '';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    toggleOrganizationField();
});
</script>
@endsection

@push('scripts')
@endpush
