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
                                        <option value="">Выберите тип</option>
                                        <option value="organization" {{ old('group_type') == 'organization' ? 'selected' : '' }}>Группа организации</option>
                                        <option value="general" {{ old('group_type') == 'general' ? 'selected' : '' }}>Общая группа</option>
                                    </select>
                                    <small class="text-muted">Группа организации — только пользователи этой организации. Общая — любые пользователи.</small>
                                    @error('group_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                
                                <div class="mb-3" id="organizationField" style="display:none;">
                                    <label class="form-label">Организация *</label>
                                    <x-searchable-select
                                        name="organization_id"
                                        url="{{ route('api.organizations.search') }}"
                                        placeholder="Начните вводить название организации..."
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
                                
                                <div class="form-check mb-3">
                                    <input type="checkbox" name="is_active" value="1" class="form-check-input" {{ old('is_active', true) ? 'checked' : '' }}>
                                    <label class="form-check-label">Активна</label>
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
    document.getElementById('organizationField').style.display = (type === 'organization') ? 'block' : 'none';
}

document.addEventListener('DOMContentLoaded', function() {
    toggleOrganizationField();
});
</script>
@endsection

@push('scripts')
@endpush
