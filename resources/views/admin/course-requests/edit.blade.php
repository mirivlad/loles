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
                            <div class="card-header bg-primary text-white"><h5 class="mb-0">Элементы заявки</h5></div>
                            <div class="card-body">
                                <div id="items-container">
                                    @foreach($courseRequest->items as $index => $item)
                                    <div class="item-row border rounded p-3 mb-3 bg-light">
                                        <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item->id }}">
                                        <div class="row g-2">
                                            <div class="col-md-4">
                                                <label class="form-label small">Курс *</label>
                                                <select name="items[{{ $index }}][course_id]" class="form-select form-select-sm" required>
                                                    @foreach($courses as $id => $title)
                                                    <option value="{{ $id }}" {{ $item->course_id == $id ? 'selected' : '' }}>{{ $title }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small">Пользователь</label>
                                                <input type="text" class="form-control form-control-sm" value="{{ $item->user_id }}" placeholder="ID пользователей" name="items[{{ $index }}][user_id]">
                                                <small class="text-muted">Или</small>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small">Группа</label>
                                                <input type="text" class="form-control form-control-sm" value="{{ $item->group_id }}" placeholder="ID групп" name="items[{{ $index }}][group_id]">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small">Дата начала *</label>
                                                <input type="date" name="items[{{ $index }}][start_date]" class="form-control form-control-sm" value="{{ $item->start_date->format('Y-m-d') }}" required>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label small">Дата окончания</label>
                                                <input type="date" name="items[{{ $index }}][end_date]" class="form-control form-control-sm" value="{{ $item->end_date?->format('Y-m-d') }}">
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-danger mt-2 remove-item"><i class="bi bi-trash"></i> Удалить</button>
                                    </div>
                                    @endforeach
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

@push('scripts')
<script>
let itemCount = {{ $courseRequest->items->count() }};

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
                <input type="text" class="form-control form-control-sm" placeholder="ID пользователей" name="items[${itemCount}][user_id]">
                <small class="text-muted">Или</small>
            </div>
            <div class="col-md-3">
                <label class="form-label small">Группа</label>
                <input type="text" class="form-control form-control-sm" placeholder="ID групп" name="items[${itemCount}][group_id]">
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
