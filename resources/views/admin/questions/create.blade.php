@extends('layouts.app')
@section('title', 'Добавить вопрос')
@section('content')
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block sidebar"><div class="position-sticky pt-3">@include('partials._sidebar')</div></nav>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Добавить вопрос</h1>
                <a href="{{ route('admin.tests.questions.index', $test) }}" class="btn btn-secondary btn-sm">Назад</a>
            </div>
            <form action="{{ route('admin.tests.questions.store', $test) }}" method="POST" id="questionForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="type" id="questionType" value="multiple_choice">
                <div class="row">
                    <div class="col-md-8">
                        <div class="card shadow-sm mb-3">
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Тип вопроса *</label>
                                    <select name="type_select" id="typeSelect" class="form-select" onchange="updateQuestionType()">
                                        <option value="multiple_choice">Множественный выбор</option>
                                        <option value="matching">Соответствие</option>
                                        <option value="ordering">Правильный порядок</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Текст вопроса *</label>
                                    <textarea name="question_text" id="questionText" class="form-control" rows="5">{{ old('question_text') }}</textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Пояснение</label>
                                    <textarea name="explanation" id="questionExplanation" class="form-control" rows="3">{{ old('explanation') }}</textarea>
                                    <small class="text-muted">Отображается после ответа</small>
                                </div>
                                
                                <!-- Ответы для multiple_choice -->
                                <div id="answersSection" class="mb-3">
                                    <label class="form-label">Ответы</label>
                                    <div id="answersContainer">
                                        <div class="input-group mb-2">
                                            <input type="text" name="answers[0][text]" class="form-control" placeholder="Текст ответа">
                                            <input type="file" name="answers[0][image]" class="form-control" accept="image/*">
                                            <input type="hidden" name="answers[0][is_correct]" value="0">
                                            <button type="button" class="btn btn-outline-success" onclick="toggleCorrect(this)"><i class="bi bi-circle"></i></button>
                                            <button type="button" class="btn btn-outline-danger" onclick="removeAnswer(this)"><i class="bi bi-trash"></i></button>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-secondary" onclick="addAnswer()">+ Добавить ответ</button>
                                </div>
                                
                                <!-- Пары для matching -->
                                <div id="matchingSection" class="mb-3" style="display:none;">
                                    <label class="form-label">Пары для соответствия</label>
                                    <div id="matchingContainer"></div>
                                    <button type="button" class="btn btn-sm btn-secondary" onclick="addMatchingPair()">+ Добавить пару</button>
                                </div>
                                
                                <!-- Элементы для ordering -->
                                <div id="orderingSection" class="mb-3" style="display:none;">
                                    <label class="form-label">Элементы для сортировки</label>
                                    <div id="orderingContainer"></div>
                                    <button type="button" class="btn btn-sm btn-secondary" onclick="addOrderingItem()">+ Добавить элемент</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card shadow-sm mb-3">
                            <div class="card-body">
                                <h5>Настройки</h5>
                                <div class="mb-3">
                                    <label class="form-label">Баллы</label>
                                    <input type="number" name="score" class="form-control" value="{{ old('score', 1) }}" min="1">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Порядок</label>
                                    <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', 0) }}">
                                </div>
                                <div class="form-check mb-3">
                                    <input type="checkbox" name="is_required" value="1" class="form-check-input" {{ old('is_required', true) ? 'checked' : '' }}>
                                    <label class="form-check-label">Обязательный вопрос</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Создать вопрос</button>
                <a href="{{ route('admin.tests.questions.index', $test) }}" class="btn btn-secondary">Отмена</a>
            </form>
        </main>
    </div>
</div>
<!-- TinyMCE -->
<script src="{{ asset('tinymce/js/tinymce/tinymce.min.js') }}"></script>
<script>
// Инициализация TinyMCE после загрузки DOM
document.addEventListener('DOMContentLoaded', function() {
    tinymce.init({
        selector: '#questionText, #questionExplanation',
        license_key: 'gpl',
        language: 'ru',
        skin: 'oxide',
        content_css: 'default',
        height: 300,
        plugins: 'image link table lists code',
        toolbar: 'undo redo | formatselect | bold italic | alignleft aligncenter alignright | bullist numlist | image link | code',
        image_title: true,
        automatic_uploads: false,
        file_picker_types: 'image',
        file_picker_callback: function(cb, value, meta) {
            const input = document.createElement('input');
            input.setAttribute('type', 'file');
            input.setAttribute('accept', 'image/*');
            input.addEventListener('change', function(e) {
                const file = e.target.files[0];
                const reader = new FileReader();
                reader.addEventListener('load', function() {
                    const id = 'blobid' + (new Date()).getTime();
                    const blobCache = tinymce.activeEditor.editorUpload.blobCache;
                    const base64 = reader.result.split(',')[1];
                    const blobInfo = blobCache.create(id, file, base64);
                    blobCache.add(blobInfo);
                    cb(blobInfo.blobUri(), { title: file.name });
                });
                reader.readAsDataURL(file);
            });
            input.click();
        }
    });
});

function updateQuestionType() {
    const type = document.getElementById('typeSelect').value;
    document.getElementById('questionType').value = type;
    document.getElementById('answersSection').style.display = (type === 'multiple_choice') ? 'block' : 'none';
    document.getElementById('matchingSection').style.display = (type === 'matching') ? 'block' : 'none';
    document.getElementById('orderingSection').style.display = (type === 'ordering') ? 'block' : 'none';
}

function addAnswer() {
    const container = document.getElementById('answersContainer');
    const index = container.children.length;
    const div = document.createElement('div');
    div.className = 'input-group mb-2';
    div.innerHTML = `<input type="text" name="answers[${index}][text]" class="form-control" placeholder="Текст ответа"><input type="file" name="answers[${index}][image]" class="form-control" accept="image/*"><input type="hidden" name="answers[${index}][is_correct]" value="0"><button type="button" class="btn btn-outline-success" onclick="toggleCorrect(this)"><i class="bi bi-circle"></i></button><button type="button" class="btn btn-outline-danger" onclick="removeAnswer(this)"><i class="bi bi-trash"></i></button>`;
    container.appendChild(div);
}
function removeAnswer(btn) { btn.parentElement.remove(); }
function toggleCorrect(btn) {
    const inputGroup = btn.parentElement;
    const hidden = inputGroup.querySelector('input[type="hidden"][name*="is_correct"]');
    if (hidden.value === '0') { 
        hidden.value = '1'; 
        btn.innerHTML = '<i class="bi bi-check-circle-fill text-success"></i>';
        btn.classList.remove('btn-outline-success');
        btn.classList.add('btn-success');
    } else { 
        hidden.value = '0'; 
        btn.innerHTML = '<i class="bi bi-circle"></i>';
        btn.classList.remove('btn-success');
        btn.classList.add('btn-outline-success');
    }
}

function addMatchingPair() {
    const container = document.getElementById('matchingContainer');
    const index = container.children.length;
    const div = document.createElement('div');
    div.className = 'input-group mb-2';
    div.innerHTML = `<input type="text" name="matching_pairs[${index}][left_text]" class="form-control" placeholder="Левая часть"><input type="text" name="matching_pairs[${index}][right_text]" class="form-control" placeholder="Правая часть"><input type="number" name="matching_pairs[${index}][match_score]" class="form-control" value="1" style="width:80px"><button type="button" class="btn btn-outline-danger" onclick="this.parentElement.remove()"><i class="bi bi-trash"></i></button>`;
    container.appendChild(div);
}

function addOrderingItem() {
    const container = document.getElementById('orderingContainer');
    const index = container.children.length;
    const div = document.createElement('div');
    div.className = 'input-group mb-2';
    div.innerHTML = `<input type="text" name="ordering_items[${index}][item_text]" class="form-control" placeholder="Элемент"><input type="number" name="ordering_items[${index}][correct_order]" class="form-control" value="${index + 1}" style="width:80px" title="Правильный порядок"><button type="button" class="btn btn-outline-danger" onclick="this.parentElement.remove()"><i class="bi bi-trash"></i></button>`;
    container.appendChild(div);
}
</script>
@endsection
