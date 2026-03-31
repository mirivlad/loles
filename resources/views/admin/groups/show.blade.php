@extends('layouts.app')
@section('title', $group->name)
@section('content')
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block sidebar"><div class="position-sticky pt-3">@include('partials._sidebar')</div></nav>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">{{ $group->name }}</h1>
                <div>
                    <button class="btn btn-warning btn-sm me-2" data-bs-toggle="modal" data-bs-target="#editGroupModal">
                        <i class="bi bi-pencil"></i> Редактировать
                    </button>
                    <a href="{{ route('admin.groups.index') }}" class="btn btn-secondary btn-sm">Назад</a>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white"><h5 class="mb-0">Информация</h5></div>
                        <div class="card-body">
                            <div><strong>Организация:</strong> {{ $group->organization?->name ?? 'Общая группа' }}</div>
                            <div><strong>Описание:</strong> {{ $group->description ?? '—' }}</div>
                            <div><strong>Статус:</strong> 
                                @if($group->is_active)
                                <span class="badge bg-success">Активна</span>
                                @else
                                <span class="badge bg-secondary">Не активна</span>
                                @endif
                            </div>
                            <div><strong>Создана:</strong> {{ $group->created_at->format('d.m.Y') }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-header bg-success text-white"><h5 class="mb-0">Статистика</h5></div>
                        <div class="card-body">
                            <div class="display-6 text-success" id="users-count">{{ $group->users->count() }}</div>
                            <div class="text-muted"><i class="bi bi-people"></i> Пользователей в группе</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-12 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bi bi-people"></i> Пользователи</h5>
                            <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#addUserModal"><i class="bi bi-plus-lg"></i></button>
                        </div>
                        <div class="card-body p-0">
                            <div id="users-list">
                                @if($group->users->count() > 0)
                                <ul class="list-group list-group-flush" id="users-ul">
                                    @foreach($group->users as $user)
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
                    <x-tags-input name="user_ids" url="{{ route('api.users.search') }}" placeholder="Начните вводить имя..." badge_color="success" />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-success">Добавить</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal редактирования группы -->
<div class="modal fade" id="editGroupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editGroupForm">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Редактировать группу</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Название *</label>
                        <input type="text" name="name" class="form-control" value="{{ $group->name }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Описание</label>
                        <textarea name="description" class="form-control" rows="3">{{ $group->description }}</textarea>
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" name="is_active" value="1" class="form-check-input" {{ $group->is_active ? 'checked' : '' }}>
                        <label class="form-check-label">Активна</label>
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
    
    fetch('/admin/groups/{{ $group->id }}/users/0/add', {
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
        } else {
            alert(data.message || 'Ошибка добавления');
        }
    })
    .catch(err => console.error(err));
});

// AJAX удаление пользователя
function removeUser(userId) {
    if (!confirm('Удалить пользователя из группы?')) return;
    
    fetch('/admin/groups/{{ $group->id }}/users/' + userId + '/remove', {
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

// AJAX редактирование группы
document.getElementById('editGroupForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('/admin/groups/{{ $group->id }}', {
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
    const editModal = new bootstrap.Modal(document.getElementById('editGroupModal'));
    editModal.show();
    @endif
});
</script>
@endpush
@endsection
