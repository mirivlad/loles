@extends('layouts.app')
@section('title', $course->title)
@section('content')
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block sidebar"><div class="position-sticky pt-3">@include('partials._sidebar')</div></nav>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">{{ $course->title }}</h1>
                <div>
                    <a href="{{ route('admin.courses.edit', $course) }}" class="btn btn-warning btn-sm me-2">Редактировать</a>
                    <a href="{{ route('admin.courses.index') }}" class="btn btn-secondary btn-sm">Назад</a>
                </div>
            </div>
            
            @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div><strong>Категория:</strong> {{ $course->category?->name ?? '—' }}</div>
                            <div><strong>Slug:</strong> {{ $course->slug ?? '—' }}</div>
                            <div><strong>Статус:</strong> @if($course->is_active)<span class="badge bg-success">Активен</span>@else<span class="badge bg-secondary">Не активен</span>@endif</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div><strong>Модулей:</strong> {{ $course->modules()->whereNull('parent_id')->count() }}</div>
                            <div><strong>Тестов:</strong> {{ $course->tests->count() }}</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Модули курса -->
            <div class="card shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-layers"></i> Модули курса</h5>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addModuleModal">
                        <i class="bi bi-plus-lg"></i> Добавить модуль
                    </button>
                </div>
                <div class="card-body">
                    @if($course->modules()->whereNull('parent_id')->count() > 0)
                    <div class="accordion" id="modulesAccordion">
                        @foreach($course->modules()->whereNull('parent_id')->orderBy('sort_order')->get() as $module)
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading{{ $module->id }}">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $module->id }}">
                                    <span class="badge bg-{{ $module->type === 'section' ? 'primary' : 'secondary' }} me-2">{{ $module->type }}</span>
                                    {{ $module->title }}
                                    @if(!$module->is_active)<span class="badge bg-warning ms-2">Не активен</span>@endif
                                </button>
                            </h2>
                            <div id="collapse{{ $module->id }}" class="accordion-collapse collapse" data-bs-parent="#modulesAccordion">
                                <div class="accordion-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            @if($module->duration_minutes)<i class="bi bi-clock"></i> {{ $module->duration_minutes }} мин.@endif
                                            @if($module->is_required)<span class="badge bg-info ms-2">Обязательный</span>@endif
                                        </div>
                                        <div>
                                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModuleModal{{ $module->id }}"><i class="bi bi-pencil"></i></button>
                                            <form action="{{ route('admin.courses.modules.destroy', [$course, $module]) }}" method="POST" class="d-inline" onsubmit="return confirm('Удалить модуль?')">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                            </form>
                                        </div>
                                    </div>
                                    
                                    @if($module->content)<p>{{ $module->content }}</p>@endif
                                    @if($module->video_url)<div class="mb-2"><i class="bi bi-camera-video"></i> <a href="{{ $module->video_url }}" target="_blank">Видео</a></div>@endif
                                    @if($module->file_path)<div class="mb-2"><i class="bi bi-file-earmark"></i> <a href="/storage/{{ $module->file_path }}" target="_blank">Файл</a></div>@endif
                                    @if($module->external_url)<div class="mb-2"><i class="bi bi-link-45deg"></i> <a href="{{ $module->external_url }}" target="_blank">Внешняя ссылка</a></div>@endif
                                    @if($module->test_id)<div class="mb-2"><i class="bi bi-file-earmark-text"></i> Тест: {{ $module->test?->title }}</div>@endif
                                    
                                    <!-- Дочерние модули -->
                                    @if($module->children()->count() > 0)
                                    <div class="mt-3 ps-3 border-start">
                                        @foreach($module->children()->orderBy('sort_order')->get() as $child)
                                        <div class="d-flex justify-content-between align-items-center py-2">
                                            <div>
                                                <span class="badge bg-{{ $child->type === 'lesson' ? 'success' : 'info' }} me-2">{{ $child->type }}</span>
                                                {{ $child->title }}
                                            </div>
                                            <div>
                                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModuleModal{{ $child->id }}"><i class="bi bi-pencil"></i></button>
                                                <form action="{{ route('admin.courses.modules.destroy', [$course, $child]) }}" method="POST" class="d-inline" onsubmit="return confirm('Удалить?')">
                                                    @csrf @method('DELETE')
                                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                                </form>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                    @endif
                                    
                                    <!-- Modal редактирования -->
                                    <div class="modal fade" id="editModuleModal{{ $module->id }}" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <form action="{{ route('admin.courses.modules.update', [$course, $module]) }}" method="POST" enctype="multipart/form-data">
                                                    @csrf @method('PUT')
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Редактировать модуль</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label">Название *</label>
                                                            <input type="text" name="title" class="form-control" value="{{ $module->title }}" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Тип *</label>
                                                            <select name="type" class="form-select" required>
                                                                <option value="section" {{ $module->type === 'section' ? 'selected' : '' }}>Раздел</option>
                                                                <option value="lesson" {{ $module->type === 'lesson' ? 'selected' : '' }}>Урок (текст)</option>
                                                                <option value="video" {{ $module->type === 'video' ? 'selected' : '' }}>Видео</option>
                                                                <option value="file" {{ $module->type === 'file' ? 'selected' : '' }}>Файл</option>
                                                                <option value="link" {{ $module->type === 'link' ? 'selected' : '' }}>Внешняя ссылка</option>
                                                                <option value="test" {{ $module->type === 'test' ? 'selected' : '' }}>Тест</option>
                                                            </select>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Контент (для урока)</label>
                                                            <textarea name="content" class="form-control" rows="3">{{ $module->content }}</textarea>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">URL видео</label>
                                                            <input type="text" name="video_url" class="form-control" value="{{ $module->video_url }}">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Внешняя ссылка</label>
                                                            <input type="text" name="external_url" class="form-control" value="{{ $module->external_url }}">
                                                        </div>
                                                        @if($module->type === 'test')
                                                        <div class="mb-3">
                                                            <label class="form-label">Тест</label>
                                                            <select name="test_id" class="form-select">
                                                                <option value="">Не выбран</option>
                                                                @foreach($course->tests as $test)
                                                                <option value="{{ $test->id }}" {{ $module->test_id == $test->id ? 'selected' : '' }}>{{ $test->title }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        @endif
                                                        <div class="row">
                                                            <div class="col-md-4 mb-3">
                                                                <label class="form-label">Порядок</label>
                                                                <input type="number" name="sort_order" class="form-control" value="{{ $module->sort_order }}">
                                                            </div>
                                                            <div class="col-md-4 mb-3">
                                                                <label class="form-label">Длительность (мин)</label>
                                                                <input type="number" name="duration_minutes" class="form-control" value="{{ $module->duration_minutes }}">
                                                            </div>
                                                            <div class="col-md-4 mb-3">
                                                                <div class="form-check mt-4">
                                                                    <input type="checkbox" name="is_required" value="1" class="form-check-input" {{ $module->is_required ? 'checked' : '' }}>
                                                                    <label class="form-check-label">Обязательный</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="form-check">
                                                            <input type="checkbox" name="is_active" value="1" class="form-check-input" {{ $module->is_active ? 'checked' : '' }}>
                                                            <label class="form-check-label">Активен</label>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                                                        <button type="submit" class="btn btn-primary">Сохранить</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <p class="text-muted text-center py-4">Модулей пока нет. Добавьте первый модуль!</p>
                    @endif
                </div>
            </div>
            
            <!-- Тесты -->
            <div class="card shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-file-earmark-text"></i> Тесты</h5>
                    <a href="{{ route('admin.courses.tests.create', $course) }}" class="btn btn-sm btn-primary"><i class="bi bi-plus"></i> Добавить тест</a>
                </div>
                <div class="card-body">
                    @if($course->tests->count() > 0)
                    <ul class="list-group list-group-flush">
                        @foreach($course->tests as $test)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <a href="{{ route('admin.courses.tests.show', [$course, $test]) }}" class="text-decoration-none">{{ $test->title }}</a>
                            <div>
                                <span class="badge bg-secondary me-2">{{ $test->type }}</span>
                                <a href="{{ route('admin.courses.tests.edit', [$course, $test]) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            </div>
                        </li>
                        @endforeach
                    </ul>
                    @else
                    <p class="text-muted mb-0">Тестов пока нет</p>
                    @endif
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal добавления модуля -->
<div class="modal fade" id="addModuleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('admin.courses.modules.store', $course) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Добавить модуль</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Родительский модуль</label>
                        <select name="parent_id" class="form-select">
                            <option value="">Без родителя (корневой)</option>
                            @foreach($course->modules()->whereNull('parent_id')->get() as $module)
                            <option value="{{ $module->id }}">{{ $module->title }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Оставьте пустым для корневого модуля</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Название *</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Тип *</label>
                        <select name="type" class="form-select" required onchange="toggleContentFields()">
                            <option value="section">Раздел</option>
                            <option value="lesson">Урок (текст)</option>
                            <option value="video">Видео</option>
                            <option value="file">Файл</option>
                            <option value="link">Внешняя ссылка</option>
                            <option value="test">Тест</option>
                        </select>
                    </div>
                    <div id="contentField" class="mb-3" style="display:none;">
                        <label class="form-label">Контент</label>
                        <textarea name="content" class="form-control" rows="3"></textarea>
                    </div>
                    <div id="videoField" class="mb-3" style="display:none;">
                        <label class="form-label">URL видео</label>
                        <input type="text" name="video_url" class="form-control" placeholder="https://youtube.com/...">
                    </div>
                    <div id="fileField" class="mb-3" style="display:none;">
                        <label class="form-label">Файл</label>
                        <input type="file" name="file" class="form-control">
                    </div>
                    <div id="linkField" class="mb-3" style="display:none;">
                        <label class="form-label">Внешняя ссылка</label>
                        <input type="text" name="external_url" class="form-control" placeholder="https://...">
                    </div>
                    <div id="testField" class="mb-3" style="display:none;">
                        <label class="form-label">Тест</label>
                        <select name="test_id" class="form-select">
                            <option value="">Не выбран</option>
                            @foreach($course->tests as $test)
                            <option value="{{ $test->id }}">{{ $test->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Порядок</label>
                            <input type="number" name="sort_order" class="form-control" value="0">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Длительность (мин)</label>
                            <input type="number" name="duration_minutes" class="form-control">
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="form-check mt-4">
                                <input type="checkbox" name="is_required" value="1" class="form-check-input">
                                <label class="form-check-label">Обязательный</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="is_active" value="1" class="form-check-input" checked>
                        <label class="form-check-label">Активен</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">Добавить</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function toggleContentFields() {
    const type = document.querySelector('select[name="type"]').value;
    document.getElementById('contentField').style.display = (type === 'lesson') ? 'block' : 'none';
    document.getElementById('videoField').style.display = (type === 'video') ? 'block' : 'none';
    document.getElementById('fileField').style.display = (type === 'file') ? 'block' : 'none';
    document.getElementById('linkField').style.display = (type === 'link') ? 'block' : 'none';
    document.getElementById('testField').style.display = (type === 'test') ? 'block' : 'none';
}

// Инициализация при загрузке
document.addEventListener('DOMContentLoaded', function() {
    toggleContentFields();
});
</script>
@endpush
@endsection
