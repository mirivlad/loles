@extends('layouts.app')
@section('title', 'Назначение: ' . $courseModel->title)
@section('content')
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block sidebar"><div class="position-sticky pt-3">@include('partials._sidebar')</div></nav>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">{{ $courseModel->title }}</h1>
                <div>
                    <button class="btn btn-warning btn-sm me-2" data-bs-toggle="modal" data-bs-target="#editAssignmentModal">
                        <i class="bi bi-pencil"></i> Редактировать
                    </button>
                    <a href="{{ route('admin.course-assignments.index') }}" class="btn btn-secondary btn-sm">Назад</a>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-8">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white"><h5 class="mb-0">Период</h5></div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6">
                                    <div><strong>Начало:</strong> {{ \Carbon\Carbon::parse($start_date)->format('d.m.Y') }}</div>
                                    <div><strong>Окончание:</strong> {{ $end_date ? \Carbon\Carbon::parse($end_date)->format('d.m.Y') : 'Бессрочно' }}</div>
                                </div>
                                @if($assignments->first()?->note)
                                <div class="col-6 border-start">
                                    <div><strong>Заметка:</strong></div>
                                    <p class="mb-0 text-muted">{{ $assignments->first()->note }}</p>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-success text-white"><h5 class="mb-0">Статистика</h5></div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-4">
                                    <div class="display-6 text-success" id="individual-count">{{ $individual->count() }}</div>
                                    <div class="text-muted"><i class="bi bi-person"></i></div>
                                </div>
                                <div class="col-4">
                                    <div class="display-6 text-info" id="group-count">{{ $groups->count() }}</div>
                                    <div class="text-muted"><i class="bi bi-people"></i></div>
                                </div>
                                <div class="col-4">
                                    <div class="display-6 text-primary" id="organization-count">{{ $organizations->count() }}</div>
                                    <div class="text-muted"><i class="bi bi-building"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bi bi-person"></i> Пользователи</h5>
                            <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#addUserModal"><i class="bi bi-plus-lg"></i></button>
                        </div>
                        <div class="card-body p-0">
                            <div id="users-list">
                                @if($individual->count() > 0)
                                <ul class="list-group list-group-flush" id="users-ul">
                                    @foreach($individual as $a)
                                    <li class="list-group-item d-flex justify-content-between align-items-center" id="user-li-{{ $a->id }}">
                                        <a href="{{ route('admin.users.show', $a->user) }}">{{ $a->user?->name ?? '—' }}</a>
                                        <button onclick="removeAssignment({{ $a->id }}, 'user')" class="btn btn-sm btn-outline-danger"><i class="bi bi-x-lg"></i></button>
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
                
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bi bi-people"></i> Группы</h5>
                            <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#addGroupModal"><i class="bi bi-plus-lg"></i></button>
                        </div>
                        <div class="card-body p-0">
                            <div id="groups-list">
                                @if($groups->count() > 0)
                                <ul class="list-group list-group-flush" id="groups-ul">
                                    @foreach($groups as $a)
                                    <li class="list-group-item d-flex justify-content-between align-items-center" id="group-li-{{ $a->id }}">
                                        <a href="{{ route('admin.groups.show', $a->group) }}">{{ $a->group?->name ?? '—' }}</a>
                                        <button onclick="removeAssignment({{ $a->id }}, 'group')" class="btn btn-sm btn-outline-danger"><i class="bi bi-x-lg"></i></button>
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
                
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bi bi-building"></i> Организации</h5>
                            <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#addOrganizationModal"><i class="bi bi-plus-lg"></i></button>
                        </div>
                        <div class="card-body p-0">
                            <div id="organizations-list">
                                @if($organizations->count() > 0)
                                <ul class="list-group list-group-flush" id="organizations-ul">
                                    @foreach($organizations as $a)
                                    <li class="list-group-item d-flex justify-content-between align-items-center" id="organization-li-{{ $a->id }}">
                                        <a href="{{ route('admin.organizations.show', $a->organization) }}">{{ $a->organization?->name ?? '—' }}</a>
                                        <button onclick="removeAssignment({{ $a->id }}, 'organization')" class="btn btn-sm btn-outline-danger"><i class="bi bi-x-lg"></i></button>
                                    </li>
                                    @endforeach
                                </ul>
                                @else
                                <p class="text-muted text-center py-4" id="organizations-empty">Нет организаций</p>
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
                <input type="hidden" name="course_ids" value="{{ $courseModel->id }}">
                <input type="hidden" name="start_date" value="{{ $start_date }}">
                <input type="hidden" name="end_date" value="{{ $end_date }}">
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

<!-- Modal добавления группы -->
<div class="modal fade" id="addGroupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="addGroupForm">
                @csrf
                <input type="hidden" name="course_ids" value="{{ $courseModel->id }}">
                <input type="hidden" name="start_date" value="{{ $start_date }}">
                <input type="hidden" name="end_date" value="{{ $end_date }}">
                <div class="modal-header">
                    <h5 class="modal-title">Добавить группу</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <x-tags-input name="group_ids" url="{{ route('api.groups.search') }}" placeholder="Начните вводить название..." badge_color="info" />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-info">Добавить</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal добавления организации -->
<div class="modal fade" id="addOrganizationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="addOrganizationForm">
                @csrf
                <input type="hidden" name="course_ids" value="{{ $courseModel->id }}">
                <input type="hidden" name="start_date" value="{{ $start_date }}">
                <input type="hidden" name="end_date" value="{{ $end_date }}">
                <div class="modal-header">
                    <h5 class="modal-title">Добавить организацию</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <x-tags-input name="organization_ids" url="{{ route('api.organizations.search') }}" placeholder="Начните вводить название..." badge_color="primary" />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">Добавить</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal редактирования назначения -->
<div class="modal fade" id="editAssignmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.course-assignments.store') }}" method="POST">
                @csrf
                <input type="hidden" name="course_ids" value="{{ $courseModel->id }}">
                <input type="hidden" name="start_date" value="{{ $start_date }}">
                <div class="modal-header">
                    <h5 class="modal-title">Редактировать назначение</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Дата окончания</label>
                        <input type="date" name="end_date" class="form-control" value="{{ $end_date }}">
                        <small class="text-muted">Оставьте пустым для бессрочного</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Заметка</label>
                        <textarea name="note" class="form-control" rows="3">{{ $assignments->first()?->note }}</textarea>
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
// Автоматическое открытие modal редактирования если в URL есть #edit
document.addEventListener('DOMContentLoaded', function() {
    if (window.location.hash === '#edit') {
        const editModal = new bootstrap.Modal(document.getElementById('editAssignmentModal'));
        editModal.show();
    }
});

// AJAX добавление
function setupAjaxForm(formId, type) {
    document.getElementById(formId).addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        fetch('{{ route('admin.course-assignments.store') }}', {
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
}

setupAjaxForm('addUserForm', 'user');
setupAjaxForm('addGroupForm', 'group');
setupAjaxForm('addOrganizationForm', 'organization');

// AJAX удаление
function removeAssignment(id, type) {
    if (!confirm('Удалить это назначение?')) return;
    
    fetch('/admin/course-assignments/' + id, {
        method: 'DELETE',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Удаляем элемент из списка
            const li = document.getElementById(type + '-li-' + id);
            if (li) li.remove();
            
            // Обновляем счётчик
            const countEl = document.getElementById(type + '-count');
            if (countEl) countEl.textContent = parseInt(countEl.textContent) - 1;
            
            // Показываем "нет элементов" если пусто
            const ul = document.getElementById(type + 's-ul');
            const empty = document.getElementById(type + 's-empty');
            if (ul && ul.children.length === 0 && empty) {
                empty.style.display = 'block';
            }
        }
    })
    .catch(err => console.error(err));
}
</script>
@endpush
@endsection
