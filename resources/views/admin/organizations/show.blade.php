@extends('layouts.app')
@section('title', $organization->name)
@section('content')
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block sidebar"><div class="position-sticky pt-3">@include('partials._sidebar')</div></nav>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">{{ $organization->name }}</h1>
                <div>
                    <button class="btn btn-warning btn-sm me-2" data-bs-toggle="modal" data-bs-target="#editOrganizationModal">
                        <i class="bi bi-pencil"></i> Редактировать
                    </button>
                    <a href="{{ route('admin.organizations.index') }}" class="btn btn-secondary btn-sm">Назад</a>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white"><h5 class="mb-0">Информация</h5></div>
                        <div class="card-body">
                            <div><strong>ИНН/КПП:</strong> {{ $organization->inn ?? '—' }} / {{ $organization->kpp ?? '—' }}</div>
                            <div><strong>Адрес:</strong> {{ $organization->address ?? '—' }}</div>
                            <div><strong>Телефон:</strong> {{ $organization->phone ?? '—' }}</div>
                            <div><strong>Email:</strong> {{ $organization->email ?? '—' }}</div>
                            <div><strong>Статус:</strong> 
                                @if($organization->is_active)
                                <span class="badge bg-success">Активна</span>
                                @else
                                <span class="badge bg-secondary">Не активна</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-header bg-success text-white"><h5 class="mb-0">Статистика</h5></div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-6 mb-3">
                                    <div class="display-6 text-success" id="users-count">{{ $organization->users->count() }}</div>
                                    <div class="text-muted"><i class="bi bi-people"></i> Пользователей</div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="display-6 text-info" id="groups-count">{{ $organization->groups->count() }}</div>
                                    <div class="text-muted"><i class="bi bi-people-fill"></i> Групп</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bi bi-people"></i> Пользователи</h5>
                            <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#addUserModal"><i class="bi bi-plus-lg"></i></button>
                        </div>
                        <div class="card-body p-0">
                            <div id="users-list">
                                @if($organization->users->count() > 0)
                                <ul class="list-group list-group-flush" id="users-ul">
                                    @foreach($organization->users as $user)
                                    <li class="list-group-item d-flex justify-content-between align-items-center" id="user-li-{{ $user->id }}">
                                        <a href="{{ route('admin.users.show', $user) }}">{{ $user->name }} <small class="text-muted">({{ $user->email }})</small></a>
                                        <button onclick="removeUser({{ $user->id }})" class="btn btn-sm btn-outline-danger"><i class="bi bi-x-lg"></i></button>
                                    </li>
                                    @endforeach
                                </ul>
                                @else
                                <p class="text-muted text-center py-4" id="users-empty">Нет пользователей</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bi bi-people-fill"></i> Группы</h5>
                            <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#addGroupModal"><i class="bi bi-plus-lg"></i></button>
                        </div>
                        <div class="card-body p-0">
                            <div id="groups-list">
                                @if($organization->groups->count() > 0)
                                <ul class="list-group list-group-flush" id="groups-ul">
                                    @foreach($organization->groups as $group)
                                    <li class="list-group-item d-flex justify-content-between align-items-center" id="group-li-{{ $group->id }}">
                                        <a href="{{ route('admin.groups.show', $group) }}">{{ $group->name }}</a>
                                        <button onclick="removeGroup({{ $group->id }})" class="btn btn-sm btn-outline-danger"><i class="bi bi-x-lg"></i></button>
                                    </li>
                                    @endforeach
                                </ul>
                                @else
                                <p class="text-muted text-center py-4" id="groups-empty">Нет групп</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-12 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header"><h5 class="mb-0">Ссылки</h5></div>
                        <div class="card-body">
                            <a href="{{ route('admin.users.index', ['organization_id' => $organization->id]) }}" class="btn btn-outline-primary">
                                <i class="bi bi-people"></i> Все пользователи организации
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal добавления пользователя -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="addUserForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Добавить пользователя</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <x-tags-input 
                        name="user_ids" 
                        url="{{ route('api.users.search') }}" 
                        placeholder="Начните вводить имя (пользователи без организации)..." 
                        badge_color="success" 
                    />
                    <small class="text-muted">Можно добавить пользователей без организации</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-success">Добавить</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal добавления группы -->
<div class="modal fade" id="addGroupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="addGroupForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Добавить группу</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <x-tags-input 
                        name="group_ids" 
                        url="{{ route('api.groups.search') }}" 
                        placeholder="Начните вводить название (общие группы)..." 
                        badge_color="info" 
                    />
                    <small class="text-muted">Можно добавить общие группы (без организации)</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-info">Добавить</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal редактирования организации -->
<div class="modal fade" id="editOrganizationModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editOrganizationForm">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Редактировать организацию</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Название *</label>
                            <input type="text" name="name" class="form-control" value="{{ $organization->name }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">ИНН</label>
                            <input type="text" name="inn" class="form-control" value="{{ $organization->inn }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">КПП</label>
                            <input type="text" name="kpp" class="form-control" value="{{ $organization->kpp }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Телефон</label>
                            <input type="text" name="phone" class="form-control" value="{{ $organization->phone }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="{{ $organization->email }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Адрес</label>
                            <input type="text" name="address" class="form-control" value="{{ $organization->address }}">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Описание</label>
                            <textarea name="description" class="form-control" rows="3">{{ $organization->description }}</textarea>
                        </div>
                        <div class="col-md-12 mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="is_active" value="1" class="form-check-input" {{ $organization->is_active ? 'checked' : '' }}>
                                <label class="form-check-label">Активна</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-warning">Сохранить</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
// AJAX добавление пользователя
document.getElementById('addUserForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('/admin/organizations/{{ $organization->id }}/users/add', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    })
    .catch(err => console.error(err));
});

// AJAX удаление пользователя
function removeUser(userId) {
    if (!confirm('Удалить пользователя из организации?')) return;
    
    fetch('/admin/organizations/{{ $organization->id }}/users/' + userId + '/remove', {
        method: 'DELETE',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const li = document.getElementById('user-li-' + userId);
            if (li) li.remove();
            
            const countEl = document.getElementById('users-count');
            if (countEl) countEl.textContent = parseInt(countEl.textContent) - 1;
            
            const ul = document.getElementById('users-ul');
            const empty = document.getElementById('users-empty');
            if (ul && ul.children.length === 0 && empty) {
                empty.style.display = 'block';
            }
        }
    })
    .catch(err => console.error(err));
}

// AJAX добавление группы
document.getElementById('addGroupForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('/admin/organizations/{{ $organization->id }}/groups/add', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    })
    .catch(err => console.error(err));
});

// AJAX удаление группы
function removeGroup(groupId) {
    if (!confirm('Удалить группу из организации?')) return;
    
    fetch('/admin/organizations/{{ $organization->id }}/groups/' + groupId + '/remove', {
        method: 'DELETE',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const li = document.getElementById('group-li-' + groupId);
            if (li) li.remove();
            
            const countEl = document.getElementById('groups-count');
            if (countEl) countEl.textContent = parseInt(countEl.textContent) - 1;
            
            const ul = document.getElementById('groups-ul');
            const empty = document.getElementById('groups-empty');
            if (ul && ul.children.length === 0 && empty) {
                empty.style.display = 'block';
            }
        }
    })
    .catch(err => console.error(err));
}

// AJAX редактирование организации
document.getElementById('editOrganizationForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('/admin/organizations/{{ $organization->id }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-HTTP-Method-Override': 'PUT'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    })
    .catch(err => console.error(err));
});

// Автооткрытие modal редактирования
document.addEventListener('DOMContentLoaded', function() {
    @if(session('edit'))
    const editModal = new bootstrap.Modal(document.getElementById('editOrganizationModal'));
    editModal.show();
    @endif
});
</script>
@endpush
@endsection
