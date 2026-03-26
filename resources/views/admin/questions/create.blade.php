@extends('layouts.app')
@section('title', 'Добавить вопрос')
@section('content')
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block sidebar"><div class="position-sticky pt-3">@include('partials._sidebar')</div></nav>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Добавить вопрос</h1>
                <a href="{{ route('admin.courses.tests.show', [$test->course, $test]) }}" class="btn btn-secondary btn-sm">Назад</a>
            </div>
            <form action="{{ route('admin.courses.tests.questions.store', [$test->course, $test]) }}" method="POST" id="questionForm">
                @csrf
                <input type="hidden" name="type" id="questionType" value="single_choice">
                <div class="row">
                    <div class="col-md-8">
                        <div class="card shadow-sm mb-3">
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Тип вопроса *</label>
                                    <select name="type_select" id="typeSelect" class="form-select" onchange="updateQuestionType()">
                                        <option value="single_choice">Один правильный ответ</option>
                                        <option value="multiple_choice">Несколько правильных ответов</option>
                                        <option value="input">Ввод текста</option>
                                        <option value="matching">Соответствие</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Текст вопроса *</label>
                                    <textarea name="question_text" class="form-control" rows="3" required>{{ old('question_text') }}</textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Пояснение</label>
                                    <textarea name="explanation" class="form-control" rows="2">{{ old('explanation') }}</textarea>
                                    <small class="text-muted">Отображается после ответа</small>
                                </div>
                                
                                <!-- Ответы для single/multiple choice -->
                                <div id="answersSection" class="mb-3">
                                    <label class="form-label">Ответы</label>
                                    <div id="answersContainer">
                                        <div class="input-group mb-2">
                                            <input type="text" name="answers[0][text]" class="form-control" placeholder="Текст ответа">
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
                <a href="{{ route('admin.courses.tests.show', [$test->course, $test]) }}" class="btn btn-secondary">Отмена</a>
            </form>
        </main>
    </div>
</div>
<script>
function updateQuestionType() {
    const type = document.getElementById('typeSelect').value;
    document.getElementById('questionType').value = type;
    const answersSection = document.getElementById('answersSection');
    const matchingSection = document.getElementById('matchingSection');
    answersSection.style.display = (type === 'single_choice' || type === 'multiple_choice') ? 'block' : 'none';
    matchingSection.style.display = (type === 'matching') ? 'block' : 'none';
}
function addAnswer() {
    const container = document.getElementById('answersContainer');
    const index = container.children.length;
    const div = document.createElement('div');
    div.className = 'input-group mb-2';
    div.innerHTML = `<input type="text" name="answers[${index}][text]" class="form-control" placeholder="Текст ответа"><input type="hidden" name="answers[${index}][is_correct]" value="0"><button type="button" class="btn btn-outline-success" onclick="toggleCorrect(this)"><i class="bi bi-circle"></i></button><button type="button" class="btn btn-outline-danger" onclick="removeAnswer(this)"><i class="bi bi-trash"></i></button>`;
    container.appendChild(div);
}
function removeAnswer(btn) { btn.parentElement.remove(); }
function toggleCorrect(btn) {
    const hidden = btn.previousElementSibling;
    if (hidden.value === '0') { hidden.value = '1'; btn.innerHTML = '<i class="bi bi-check-circle-fill text-success"></i>'; }
    else { hidden.value = '0'; btn.innerHTML = '<i class="bi bi-circle"></i>'; }
}
function addMatchingPair() {
    const container = document.getElementById('matchingContainer');
    const index = container.children.length;
    const div = document.createElement('div');
    div.className = 'input-group mb-2';
    div.innerHTML = `<input type="text" name="matching_pairs[${index}][left_text]" class="form-control" placeholder="Левая часть"><input type="text" name="matching_pairs[${index}][right_text]" class="form-control" placeholder="Правая часть"><input type="number" name="matching_pairs[${index}][match_score]" class="form-control" value="1" style="width:80px" title="Баллы"><button type="button" class="btn btn-outline-danger" onclick="this.parentElement.remove()"><i class="bi bi-trash"></i></button>`;
    container.appendChild(div);
}
</script>
@endsection
