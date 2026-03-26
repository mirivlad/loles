@extends('layouts.app')
@section('title', $question->question_text)
@section('content')
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block sidebar"><div class="position-sticky pt-3">@include('partials._sidebar')</div></nav>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Вопрос #{{ $question->id }}</h1>
                <div>
                    <a href="{{ route('admin.tests.questions.edit', [$test, $question]) }}" class="btn btn-warning btn-sm me-2">Редактировать</a>
                    <a href="{{ route('admin.tests.questions.index', $test) }}" class="btn btn-secondary btn-sm">Назад</a>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-8 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white"><h5 class="mb-0">Информация</h5></div>
                        <div class="card-body">
                            <table class="table table-sm">
                                <tr><th>Тип:</th><td><span class="badge bg-info">{{ $question->type }}</span></td></tr>
                                <tr><th>Вопрос:</th><td>{{ $question->question_text }}</td></tr>
                                <tr><th>Пояснение:</th><td>{{ $question->explanation ?? '—' }}</td></tr>
                                <tr><th>Баллы:</th><td>{{ $question->score }}</td></tr>
                                <tr><th>Порядок:</th><td>{{ $question->sort_order }}</td></tr>
                                <tr><th>Обязательный:</th><td>@if($question->is_required)<span class="badge bg-warning">Да</span>@else<span class="badge bg-secondary">Нет</span>@endif</td></tr>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm">
                        @if($question->type === 'matching')
                        <div class="card-header"><h5 class="mb-0">Пары</h5></div>
                        <div class="card-body">
                            @if($question->matchingPairs->count() > 0)
                            <ul class="list-group list-group-flush">
                                @foreach($question->matchingPairs as $pair)
                                <li class="list-group-item"><strong>{{ $pair->left_text }}</strong> ↔ <strong>{{ $pair->right_text }}</strong> ({{ $pair->match_score }} балл.)</li>
                                @endforeach
                            </ul>
                            @else
                            <p class="text-muted mb-0">Нет пар</p>
                            @endif
                        </div>
                        @else
                        <div class="card-header"><h5 class="mb-0">Ответы</h5></div>
                        <div class="card-body">
                            @if($question->answers->count() > 0)
                            <ul class="list-group list-group-flush">
                                @foreach($question->answers as $answer)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    {{ Str::limit($answer->answer_text, 50) }}
                                    @if($answer->is_correct)<span class="badge bg-success"><i class="bi bi-check"></i></span>@endif
                                </li>
                                @endforeach
                            </ul>
                            @else
                            <p class="text-muted mb-0">Нет ответов</p>
                            @endif
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
@endsection
