@extends('layouts.app')
@section('title', 'Создать заявку')
@section('content')
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block sidebar"><div class="position-sticky pt-3">@include('partials._sidebar')</div></nav>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Создать заявку</h1>
                <a href="{{ route('admin.course-requests.index') }}" class="btn btn-secondary btn-sm">Назад</a>
            </div>
            
            <form action="{{ route('admin.course-requests.store') }}" method="POST" id="createRequestForm">
                @csrf
                <div class="row">
                    <div class="col-md-8 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-primary text-white"><h5 class="mb-0">Элементы заявки</h5></div>
                            <div class="card-body">
                                <div id="items-container">
                                    <div class="item-row border rounded p-3 mb-3 bg-light">
                                        <div class="row g-2">
                                            <!-- Строка 1: Курс -->
                                            <div class="col-md-12">
                                                <label class="form-label">Курс *</label>
                                                <select name="items[0][course_id]" class="form-select course-select" required>
                                                    <option value="">Выберите курс</option>
                                                    @foreach($courses as $id => $title)
                                                    <option value="{{ $id }}">{{ $title }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            
                                            <!-- Строка 2: Получатели -->
                                            <div class="col-md-4">
                                                <label class="form-label">Организация</label>
                                                <select name="items[0][organization_id]" class="form-select org-select">
                                                    <option value="">Не выбрано</option>
                                                    @foreach($organizations as $id => $name)
                                                    <option value="{{ $id }}">{{ $name }}</option>
                                                    @endforeach
                                                </select>
                                                <small class="text-muted">И</small>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Группа</label>
                                                <x-tags-input
                                                    name="items[0][group_ids]"
                                                    url="{{ route('api.groups.search') }}"
                                                    placeholder="Или выберите группы..."
                                                    badge_color="info"
                                                />
                                                <small class="text-muted">И</small>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Пользователь</label>
                                                <x-tags-input
                                                    name="items[0][user_ids]"
                                                    url="{{ route('api.users.search') }}"
                                                    placeholder="Или выберите пользователей..."
                                                    badge_color="success"
                                                />
                                            </div>
                                            
                                            <!-- Строка 3: Даты -->
                                            <div class="col-md-6">
                                                <label class="form-label">Дата начала *</label>
                                                <input type="date" name="items[0][start_date]" class="form-control" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Дата окончания</label>
                                                <input type="date" name="items[0][end_date]" class="form-control">
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-danger mt-2 remove-item" style="display:none;"><i class="bi bi-trash"></i> Удалить</button>
                                    </div>
                                </div>
                                
                                <button type="button" class="btn btn-outline-primary btn-sm" id="add-item-btn">
                                    <i class="bi bi-plus-lg"></i> Добавить элемент
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-success text-white"><h5 class="mb-0">Дополнительно</h5></div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Статус</label>
                                    <select name="status" class="form-select">
                                        <option value="pending">Ожидает подтверждения</option>
                                        @if(auth()->user()->hasRole(['Administrator', 'Manager', 'Curator']))
                                        <option value="approved" selected>Одобрена (создать назначения сразу)</option>
                                        @endif
                                    </select>
                                    <small class="text-muted">Admin/Manager/Curator могут создать сразу одобренную заявку</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Заметка</label>
                                    <textarea name="note" class="form-control" rows="4">{{ old('note') }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">Создать заявку</button>
                <a href="{{ route('admin.course-requests.index') }}" class="btn btn-secondary">Отмена</a>
            </form>
        </main>
    </div>
</div>

@push('scripts')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
let itemCount = 1;

// Функция инициализации TomSelect для нового элемента
function initTomSelectForElement(rowElement) {
    // Инициализация для курса
    const courseSelect = rowElement.querySelector('.course-select');
    if (courseSelect && !courseSelect.tomselect) {
        new TomSelect(courseSelect, {
            valueField: 'id',
            labelField: 'text',
            searchField: 'text',
            placeholder: 'Выберите курс...',
            preload: false,
            maxOptions: null,
            load: function(query, callback) {
                if (query.length < 2) return callback();
                fetch('/api/courses/search?q=' + encodeURIComponent(query))
                    .then(response => response.json())
                    .then(json => callback(json))
                    .catch(() => callback());
            }
        });
    }
    
    // Инициализация для организации
    const orgSelect = rowElement.querySelector('.org-select');
    if (orgSelect && !orgSelect.tomselect) {
        new TomSelect(orgSelect, {
            valueField: 'id',
            labelField: 'text',
            searchField: 'text',
            placeholder: 'Или оставьте пустым...',
            preload: false,
            maxOptions: null,
            load: function(query, callback) {
                if (query.length < 2) return callback();
                fetch('/api/organizations/search?q=' + encodeURIComponent(query))
                    .then(response => response.json())
                    .then(json => callback(json))
                    .catch(() => callback());
            }
        });
    }
}

// Инициализация для первого элемента при загрузке
document.addEventListener('DOMContentLoaded', function() {
    const firstRow = document.querySelector('.item-row');
    if (firstRow) {
        initTomSelectForElement(firstRow);
    }
});

document.getElementById('add-item-btn').addEventListener('click', function() {
    const container = document.getElementById('items-container');
    const newItem = document.createElement('div');
    newItem.className = 'item-row border rounded p-3 mb-3 bg-light';
    newItem.innerHTML = `
        <div class="row g-2">
            <!-- Строка 1: Курс -->
            <div class="col-md-12">
                <label class="form-label">Курс *</label>
                <select name="items[${itemCount}][course_id]" class="form-select course-select" required>
                    <option value="">Выберите курс</option>
                    @foreach($courses as $id => $title)
                    <option value="{{ $id }}">{{ $title }}</option>
                    @endforeach
                </select>
            </div>
            
            <!-- Строка 2: Получатели -->
            <div class="col-md-4">
                <label class="form-label">Организация</label>
                <select name="items[${itemCount}][organization_id]" class="form-select org-select">
                    <option value="">Не выбрано</option>
                    @foreach($organizations as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
                <small class="text-muted">И</small>
            </div>
            <div class="col-md-4">
                <label class="form-label">Группа</label>
                <input type="text" class="form-control" placeholder="ID групп (через запятую)" name="items[${itemCount}][group_ids]">
                <small class="text-muted">И</small>
            </div>
            <div class="col-md-4">
                <label class="form-label">Пользователь</label>
                <input type="text" class="form-control" placeholder="ID пользователей (через запятую)" name="items[${itemCount}][user_ids]">
            </div>
            
            <!-- Строка 3: Даты -->
            <div class="col-md-6">
                <label class="form-label">Дата начала *</label>
                <input type="date" name="items[${itemCount}][start_date]" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Дата окончания</label>
                <input type="date" name="items[${itemCount}][end_date]" class="form-control">
            </div>
        </div>
        <button type="button" class="btn btn-sm btn-outline-danger mt-2 remove-item"><i class="bi bi-trash"></i> Удалить</button>
    `;
    container.appendChild(newItem);
    
    // Инициализируем TomSelect для нового элемента
    initTomSelectForElement(newItem);
    
    itemCount++;
    
    // Показываем кнопки удаления
    document.querySelectorAll('.remove-item').forEach(btn => btn.style.display = 'inline-block');
});

// Удаление элемента
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('remove-item') || e.target.closest('.remove-item')) {
        e.target.closest('.item-row').remove();
    }
});
</script>
@endpush
@endsection
