@extends('layouts.app')
@section('title', $test->title)
@section('content')
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block sidebar"><div class="position-sticky pt-3">@include('partials._sidebar')</div></nav>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">{{ $test->title }}</h1>
                <a href="{{ route('student.tests.index') }}" class="btn btn-secondary btn-sm">Назад</a>
            </div>
            
            <div class="row">
                <div class="col-md-8 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white"><h5 class="mb-0">Информация о тесте</h5></div>
                        <div class="card-body">
                            <div><strong>Курс:</strong> {{ $test->course->title }}</div>
                            <div><strong>Вопросов:</strong> {{ $test->questions->count() }}</div>
                            @if($test->passing_score)
                            <div><strong>Проходной балл:</strong> {{ $test->passing_score }}%</div>
                            @endif
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-body text-center">
                            <button class="btn btn-success btn-lg w-100" onclick="startTest()">
                                <i class="bi bi-play-circle"></i> Начать тестирование
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card shadow-sm">
                <div class="card-header"><h5 class="mb-0">Вопросы</h5></div>
                <div class="card-body">
                    <div class="accordion" id="questionsAccordion">
                        @foreach($test->questions as $index => $question)
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading{{ $question->id }}">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $question->id }}">
                                    Вопрос {{ $index + 1 }}: {{ Str::limit($question->question_text, 100) }}
                                </button>
                            </h2>
                            <div id="collapse{{ $question->id }}" class="accordion-collapse collapse" data-bs-parent="#questionsAccordion">
                                <div class="accordion-body">
                                    <p><strong>{{ $question->question_text }}</strong></p>
                                    
                                    @if($question->type === 'multiple_choice')
                                    <div class="mb-3">
                                        @foreach($question->answers as $answer)
                                        <div class="form-check">
                                            <input class="form-check-input" type="{{ $answer->is_correct ? 'checkbox' : 'checkbox' }}" name="question_{{ $question->id }}" value="{{ $answer->id }}" id="answer_{{ $answer->id }}">
                                            <label class="form-check-label" for="answer_{{ $answer->id }}">
                                                {{ $answer->answer_text }}
                                                @if($answer->image)
                                                <br><img src="/storage/{{ $answer->image }}" alt="Ответ" style="max-width:200px;max-height:150px;margin-top:10px;">
                                                @endif
                                            </label>
                                        </div>
                                        @endforeach
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

@push('scripts')
<script>
function startTest() {
    alert('Функционал прохождения тестов будет реализован в следующем этапе');
}
</script>
@endpush
@endsection
