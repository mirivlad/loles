@extends('layouts.app')
@section('title', 'Вопросы теста')
@section('content')
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block sidebar"><div class="position-sticky pt-3">@include('partials._sidebar')</div></nav>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Вопросы: {{ $test->title }}</h1>
                <a href="{{ route('admin.tests.questions.create', $test) }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Добавить вопрос</a>
            </div>
            @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
            <div class="mb-3"><a href="{{ route('admin.courses.tests.show', [$test->course, $test]) }}" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> Назад к тесту</a></div>
            
            <div class="card shadow-sm">
                <div class="card-body">
                    @forelse($questions as $question)
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <span class="badge bg-secondary me-2">#{{ $loop->iteration }}</span>
                                    @if($question->type === 'multiple_choice')
                                        @php $correctCount = $question->answers->where('is_correct', true)->count(); @endphp
                                        @if($correctCount === 1)
                                            <span class="badge bg-info" title="Один правильный ответ"><i class="bi bi-circle"></i></span>
                                        @else
                                            <span class="badge bg-success" title="Несколько правильных ответов"><i class="bi bi-check2-square"></i></span>
                                        @endif
                                    @elseif($question->type === 'matching')
                                        <span class="badge badge-purple" title="Соответствие"><i class="bi bi-arrow-left-right"></i></span>
                                    @elseif($question->type === 'ordering')
                                        <span class="badge bg-dark" title="Правильный порядок"><i class="bi bi-sort-numeric-down"></i></span>
                                    @endif
                                    <strong>
                                        <a href="#" class="text-decoration-none" data-bs-toggle="modal" data-bs-target="#questionPreviewModal" 
                                           data-question-type="{{ $question->type }}"
                                           data-question-text="{{ htmlspecialchars($question->question_text) }}"
                                           data-answers="{{ htmlspecialchars($question->answers->toJson()) }}"
                                           data-matching-pairs="{{ htmlspecialchars($question->matchingPairs->toJson()) }}"
                                           data-ordering-items="{{ htmlspecialchars($question->orderingItems->toJson()) }}">
                                            {{ Str::limit(strip_tags($question->question_text), 100) }}
                                        </a>
                                    </strong>
                                    <div class="mt-2">
                                        <small class="text-muted">{{ $question->answers->count() }} ответов@if($question->matchingPairs->count() > 0), {{ $question->matchingPairs->count() }} пар@endif</small>
                                        @if($question->is_required)<span class="badge bg-warning ms-2"><i class="bi bi-exclamation-circle"></i> Обязательный</span>@endif
                                        <span class="badge bg-secondary ms-2">{{ $question->score }} балл(а)</span>
                                    </div>
                                </div>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('admin.tests.questions.edit', [$test, $question]) }}" class="btn btn-outline-warning"><i class="bi bi-pencil"></i></a>
                                    <form action="{{ route('admin.tests.questions.destroy', [$test, $question]) }}" method="POST" class="d-inline" onsubmit="return confirm('Удалить?')">@csrf @method('DELETE')<button class="btn btn-outline-danger"><i class="bi bi-trash"></i></button></form>
                                </div>
                            </div>
                        </div>
                    </div>
                    @empty
                    <p class="text-muted text-center py-5">Нет вопросов. Добавьте первый вопрос!</p>
                    @endforelse
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal предпросмотра вопроса -->
<div class="modal fade" id="questionPreviewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Предпросмотр вопроса</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="modalQuestionText" class="mb-4"></div>
                <div id="modalAnswersContainer"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const previewModal = document.getElementById('questionPreviewModal');
    previewModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const questionType = button.getAttribute('data-question-type');
        const questionText = button.getAttribute('data-question-text');
        const answers = JSON.parse(button.getAttribute('data-answers'));
        const matchingPairs = JSON.parse(button.getAttribute('data-matching-pairs') || '[]');
        const orderingItems = JSON.parse(button.getAttribute('data-ordering-items') || '[]');
        
        // Отображаем текст вопроса
        document.getElementById('modalQuestionText').innerHTML = questionText;
        
        const answersContainer = document.getElementById('modalAnswersContainer');
        answersContainer.innerHTML = '';
        
        // Отображаем ответы в зависимости от типа
        if (questionType === 'multiple_choice') {
            const correctCount = answers.filter(a => a.is_correct).length;
            const inputType = correctCount === 1 ? 'radio' : 'checkbox';
            const inputName = correctCount === 1 ? 'preview_answer' : 'preview_answers[]';
            
            answers.forEach((answer, index) => {
                const div = document.createElement('div');
                div.className = 'mb-3';
                let html = `<div class="form-check">
                    <input class="form-check-input" type="${inputType}" name="${inputName}" id="answer_${index}" disabled>
                    <label class="form-check-label" for="answer_${index}">`;
                if (answer.image) {
                    html += `<img src="/storage/${answer.image}" alt="Ответ" style="max-width:200px;max-height:150px;display:block;margin:10px 0;">`;
                }
                if (answer.answer_text) {
                    html += answer.answer_text;
                }
                html += `</label></div>`;
                div.innerHTML = html;
                answersContainer.appendChild(div);
            });
        } else if (questionType === 'matching') {
            let html = '<table class="table"><thead><tr><th>Левая часть</th><th>Правая часть</th></tr></thead><tbody>';
            matchingPairs.forEach((pair, index) => {
                html += `<tr><td>${pair.left_text}</td><td>${pair.right_text}</td></tr>`;
            });
            html += '</tbody></table>';
            answersContainer.innerHTML = html;
        } else if (questionType === 'ordering') {
            let html = '<ol class="list-group">';
            orderingItems.sort((a, b) => a.correct_order - b.correct_order).forEach((item, index) => {
                html += `<li class="list-group-item">${index + 1}. ${item.item_text}</li>`;
            });
            html += '</ol>';
            answersContainer.innerHTML = html;
        }
    });
});
</script>
@endsection
