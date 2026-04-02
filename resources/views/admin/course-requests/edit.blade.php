@extends('layouts.app')
@section('title', 'Редактировать заявку #' . $courseRequest->id)
@section('content')
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block sidebar"><div class="position-sticky pt-3">@include('partials._sidebar')</div></nav>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Редактировать заявку #{{ $courseRequest->id }}</h1>
                <a href="{{ route('admin.course-requests.show', $courseRequest) }}" class="btn btn-secondary btn-sm">Назад</a>
            </div>
            
            <form action="{{ route('admin.course-requests.update', $courseRequest) }}" method="POST" id="editRequestForm">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-8 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Элементы заявки</h5>
                                <button type="button" class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#addElementModal">
                                    <i class="bi bi-plus-lg"></i> Добавить элемент
                                </button>
                            </div>
                            <div class="card-body">
                                <div id="items-container">
                                    <!-- Сюда добавляются элементы -->
                                </div>
                                <input type="hidden" name="items_json" id="items_json" value="[]">
                                @error('items_json')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-success text-white"><h5 class="mb-0">Дополнительно</h5></div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Заметка</label>
                                    <textarea name="note" class="form-control" rows="4">{{ old('note', $courseRequest->note) }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                <a href="{{ route('admin.course-requests.show', $courseRequest) }}" class="btn btn-secondary">Отмена</a>
            </form>
        </main>
    </div>
</div>

<!-- Modal добавления элемента -->
<div class="modal fade" id="addElementModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Добавить элемент заявки</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addElementForm">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Курс *</label>
                            <select id="element_course_id" class="form-select" required></select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Тип получателя *</label>
                            <select id="element_recipient_type" class="form-select" onchange="toggleRecipientFields()">
                                <option value="">Выберите тип</option>
                                <option value="user">Пользователь</option>
                                <option value="group">Группа</option>
                                <option value="organization">Организация</option>
                            </select>
                        </div>
                        <div class="col-md-12" id="recipient_user_field" style="display:none;">
                            <label class="form-label">Пользователь *</label>
                            <select id="element_user_ids" multiple></select>
                        </div>
                        <div class="col-md-12" id="recipient_group_field" style="display:none;">
                            <label class="form-label">Группа *</label>
                            <select id="element_group_ids" multiple></select>
                        </div>
                        <div class="col-md-12" id="recipient_organization_field" style="display:none;">
                            <label class="form-label">Организация *</label>
                            <select id="element_organization_id" class="form-select"></select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Дата начала *</label>
                            <input type="date" id="element_start_date" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Дата окончания</label>
                            <input type="date" id="element_end_date" class="form-control">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary" id="addElementBtn">Добавить в заявку</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
// Данные элементов
let items = @json($courseRequest->items->map(fn($item) => [
    'id' => $item->id,
    'course_id' => $item->course_id,
    'course_name' => $item->course->title,
    'organization_id' => $item->organization_id,
    'organization_name' => $item->organization?->name,
    'group_ids' => $item->group_id ? [$item->group_id] : [],
    'group_names' => $item->group_id ? [$item->group->name] : [],
    'user_ids' => $item->user_id ? [$item->user_id] : [],
    'user_names' => $item->user_id ? [$item->user->name] : [],
    'start_date' => $item->start_date->format('Y-m-d'),
    'end_date' => $item->end_date?->format('Y-m-d')
]) ?? []);

let elementCounter = items.length;
let courseSelect = null, orgSelect = null, groupTags = null, userTags = null;
let modalInitialized = false;

// Переключение полей получателей
window.toggleRecipientFields = function() {
    const type = document.getElementById('element_recipient_type').value;
    document.getElementById('recipient_user_field').style.display = (type === 'user') ? 'block' : 'none';
    document.getElementById('recipient_group_field').style.display = (type === 'group') ? 'block' : 'none';
    document.getElementById('recipient_organization_field').style.display = (type === 'organization') ? 'block' : 'none';
};

// Инициализация TomSelect при ПЕРВОМ открытии modal
document.getElementById('addElementModal').addEventListener('show.bs.modal', function() {
    if (modalInitialized) return;
    
    courseSelect = new TomSelect('#element_course_id', {
        valueField: 'id', labelField: 'text', searchField: 'text',
        placeholder: 'Выберите курс...', preload: false, maxOptions: null,
        load: function(query, callback) {
            if (query.length < 2) return callback();
            fetch('/api/courses/search?q=' + encodeURIComponent(query))
                .then(response => response.json()).then(json => callback(json)).catch(() => callback());
        }
    });
    
    orgSelect = new TomSelect('#element_organization_id', {
        valueField: 'id', labelField: 'text', searchField: 'text',
        placeholder: 'Не выбрано', preload: false, maxOptions: null,
        load: function(query, callback) {
            if (query.length < 2) return callback();
            fetch('/api/organizations/search?q=' + encodeURIComponent(query))
                .then(response => response.json()).then(json => callback(json)).catch(() => callback());
        }
    });
    
    groupTags = new TomSelect('#element_group_ids', {
        valueField: 'id', labelField: 'text', searchField: 'text',
        placeholder: 'Выберите группы...', preload: false, maxOptions: null,
        plugins: ['remove_button'],
        load: function(query, callback) {
            if (query.length < 2) return callback();
            fetch('/api/groups/search?q=' + encodeURIComponent(query))
                .then(response => response.json()).then(json => callback(json)).catch(() => callback());
        }
    });
    
    userTags = new TomSelect('#element_user_ids', {
        valueField: 'id', labelField: 'text', searchField: 'text',
        placeholder: 'Выберите пользователей...', preload: false, maxOptions: null,
        plugins: ['remove_button'],
        load: function(query, callback) {
            if (query.length < 2) return callback();
            fetch('/api/users/search?q=' + encodeURIComponent(query))
                .then(response => response.json()).then(json => callback(json)).catch(() => callback());
        }
    });
    
    modalInitialized = true;
});

document.getElementById('addElementModal').addEventListener('hidden.bs.modal', function() {
    if (courseSelect) courseSelect.clear();
    if (orgSelect) orgSelect.clear();
    if (groupTags) groupTags.clear();
    if (userTags) userTags.clear();
    document.getElementById('element_start_date').value = '';
    document.getElementById('element_end_date').value = '';
});

document.getElementById('addElementBtn').addEventListener('click', function() {
    const courseId = courseSelect.getValue();
    const recipientType = document.getElementById('element_recipient_type').value;
    const startDate = document.getElementById('element_start_date').value;
    const endDate = document.getElementById('element_end_date').value;
    
    if (!courseId) { alert('Выберите курс'); return; }
    if (!recipientType) { alert('Выберите тип получателя'); return; }
    if (!startDate) { alert('Укажите дату начала'); return; }
    
    let organizationId = null, organizationName = null;
    let groupIds = [], groupNames = [];
    let userIds = [], userNames = [];
    
    if (recipientType === 'user') {
        userIds = userTags.getValue() || [];
        if (!userIds || (Array.isArray(userIds) && userIds.length === 0)) {
            alert('Выберите хотя бы одного пользователя'); return;
        }
        userIds = Array.isArray(userIds) ? userIds : [userIds];
        userNames = userIds.map(id => {
            const opt = userTags.options[id];
            return opt ? opt.text : 'Пользователь #' + id;
        }).filter(Boolean);
    } else if (recipientType === 'group') {
        groupIds = groupTags.getValue() || [];
        if (!groupIds || (Array.isArray(groupIds) && groupIds.length === 0)) {
            alert('Выберите хотя бы одну группу'); return;
        }
        groupIds = Array.isArray(groupIds) ? groupIds : [groupIds];
        groupNames = groupIds.map(id => {
            const opt = groupTags.options[id];
            return opt ? opt.text : 'Группа #' + id;
        }).filter(Boolean);
    } else if (recipientType === 'organization') {
        organizationId = orgSelect.getValue();
        if (!organizationId) { alert('Выберите организацию'); return; }
        const orgOption = orgSelect.options[organizationId];
        organizationName = orgOption ? orgOption.text : null;
    }
    
    const courseOption = courseSelect.options[courseId];
    
    items.push({
        id: elementCounter++,
        course_id: courseId,
        course_name: courseOption ? courseOption.text : 'Курс #' + courseId,
        organization_id: organizationId,
        organization_name: organizationName,
        group_ids: groupIds,
        group_names: groupNames,
        user_ids: userIds,
        user_names: userNames,
        start_date: startDate,
        end_date: endDate || null
    });
    
    updateItemsDisplay();
    bootstrap.Modal.getInstance(document.getElementById('addElementModal')).hide();
});

// Удаление элемента
window.removeItem = function(id) {
    if (!confirm('Удалить этот элемент из заявки?')) return;
    items = items.filter(item => item.id !== id);
    updateItemsDisplay();
};

function updateItemsDisplay() {
    const container = document.getElementById('items-container');
    document.getElementById('items_json').value = JSON.stringify(items);
    
    if (items.length === 0) {
        container.innerHTML = '<p class="text-muted text-center py-4">Нет элементов. Нажмите "Добавить элемент"</p>';
        return;
    }
    
    container.innerHTML = items.map((item, index) => {
        let recipients = [];
        if (item.organization_name) recipients.push(`<span class="badge bg-primary">${item.organization_name}</span>`);
        if (item.group_names && item.group_names.length > 0) {
            item.group_names.forEach(name => recipients.push(`<span class="badge bg-info">${name}</span>`));
        }
        if (item.user_names && item.user_names.length > 0) {
            item.user_names.forEach(name => recipients.push(`<span class="badge bg-success">${name}</span>`));
        }
        if (recipients.length === 0) recipients.push('<span class="text-muted">—</span>');
        
        return `
            <div class="border rounded p-3 mb-3 bg-light">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <div class="mb-2"><strong>Курс:</strong> ${item.course_name}</div>
                        <div class="mb-2"><strong>Получатели:</strong> ${recipients.join(' ')}</div>
                        <div><strong>Даты:</strong> ${item.start_date} ${item.end_date ? '→ ' + item.end_date : ''}</div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItem(${item.id})">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        `;
    }).join('');
}

window.removeItem = function(id) {
    items = items.filter(item => item.id !== id);
    updateItemsDisplay();
};

document.getElementById('editRequestForm').addEventListener('submit', function(e) {
    if (items.length === 0) {
        e.preventDefault();
        alert('Добавьте хотя бы один элемент');
    }
});

updateItemsDisplay();
</script>
@endpush
@endsection
