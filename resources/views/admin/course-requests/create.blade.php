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
                            <div class="card-header bg-primary text-white"><h5 class="mb-0">Курсы и получатели</h5></div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Организация *</label>
                                    <x-searchable-select
                                        name="organization_id"
                                        url="{{ route('api.organizations.search') }}"
                                        placeholder="Начните вводить название организации..."
                                        :required="true"
                                    />
                                    @error('organization_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                
                                <hr class="my-4">
                                
                                <h6 class="mb-3">Элементы заявки</h6>
                                <div id="items-container">
                                    <div class="item-row border rounded p-3 mb-3 bg-light">
                                        <div class="row g-2">
                                            <div class="col-md-4">
                                                <label class="form-label small">Курс *</label>
                                                <x-searchable-select
                                                    name="items[0][course_id]"
                                                    url="{{ route('api.courses.search') }}"
                                                    placeholder="Выберите курс..."
                                                    :required="true"
                                                />
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small">Пользователь</label>
                                                <x-tags-input
                                                    name="items[0][user_ids]"
                                                    url="{{ route('api.users.search') }}"
                                                    placeholder="Или выберите пользователей..."
                                                    badge_color="success"
                                                />
                                                <small class="text-muted">Или</small>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small">Группа</label>
                                                <x-tags-input
                                                    name="items[0][group_ids]"
                                                    url="{{ route('api.groups.search') }}"
                                                    placeholder="Или выберите группы..."
                                                    badge_color="info"
                                                />
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small">Дата начала *</label>
                                                <input type="date" name="items[0][start_date]" class="form-control form-control-sm" required>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small">Дата окончания</label>
                                                <input type="date" name="items[0][end_date]" class="form-control form-control-sm">
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
<script>
let itemCount = 1;

document.getElementById('add-item-btn').addEventListener('click', function() {
    const container = document.getElementById('items-container');
    const newItem = document.createElement('div');
    newItem.className = 'item-row border rounded p-3 mb-3 bg-light';
    newItem.innerHTML = `
        <div class="row g-2">
            <div class="col-md-4">
                <label class="form-label small">Курс *</label>
                <select name="items[${itemCount}][course_id]" class="form-select form-select-sm" required>
                    <option value="">Выберите курс</option>
                    @foreach($courses as $id => $title)
                    <option value="{{ $id }}">{{ $title }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small">Пользователь</label>
                <input type="text" class="form-control form-control-sm" placeholder="ID пользователей (через запятую)" name="items[${itemCount}][user_ids]">
                <small class="text-muted">Или</small>
            </div>
            <div class="col-md-3">
                <label class="form-label small">Группа</label>
                <input type="text" class="form-control form-control-sm" placeholder="ID групп (через запятую)" name="items[${itemCount}][group_ids]">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Дата начала *</label>
                <input type="date" name="items[${itemCount}][start_date]" class="form-control form-control-sm" required>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Дата окончания</label>
                <input type="date" name="items[${itemCount}][end_date]" class="form-control form-control-sm">
            </div>
        </div>
        <button type="button" class="btn btn-sm btn-outline-danger mt-2 remove-item"><i class="bi bi-trash"></i> Удалить</button>
    `;
    container.appendChild(newItem);
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
