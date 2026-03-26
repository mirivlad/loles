@extends('layouts.app')
@section('title', $test->title)
@section('content')
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block sidebar"><div class="position-sticky pt-3">@include('partials._sidebar')</div></nav>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">{{ $test->title }}</h1>
                <div>
                    <a href="{{ route('admin.courses.tests.edit', [$course, $test]) }}" class="btn btn-warning btn-sm me-2">Редактировать</a>
                    <a href="{{ route('admin.courses.tests.index', $course) }}" class="btn btn-secondary btn-sm">Назад к тестам</a>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-8 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white"><h5 class="mb-0">Информация</h5></div>
                        <div class="card-body">
                            <table class="table table-sm">
                                <tr><th>Тип:</th><td><span class="badge bg-info">{{ $test->type }}</span></td></tr>
                                <tr><th>Описание:</th><td>{{ $test->description ?? '—' }}</td></tr>
                                <tr><th>Вопросов:</th><td>{{ $test->questions->count() }}</td></tr>
                                <tr><th>Время:</th><td>{{ $test->time_limit_minutes ?? '∞' }} мин</td></tr>
                                <tr><th>Проходной балл:</th><td>{{ $test->passing_score }}%</td></tr>
                                <tr><th>Попыток:</th><td>{{ $test->max_attempts }}</td></tr>
                                <tr><th>Перемешивание:</th><td>@if($test->shuffle_questions)<span class="badge bg-success">Да</span>@else<span class="badge bg-secondary">Нет</span>@endif</td></tr>
                                <tr><th>Показ ответов:</th><td>@if($test->show_correct_answers)<span class="badge bg-success">Да</span>@else<span class="badge bg-secondary">Нет</span>@endif</td></tr>
                                <tr><th>Статус:</th><td>@if($test->is_active)<span class="badge bg-success">Активен</span>@else<span class="badge bg-secondary">Не активен</span>@endif</td></tr>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-12 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header d-flex justify-content-between">
                            <h5 class="mb-0">Вопросы ({{ $test->questions->count() }})</h5>
                            <a href="{{ route('admin.tests.questions.create', $test) }}" class="btn btn-sm btn-primary"><i class="bi bi-plus"></i></a>
                        </div>
                        <div class="card-body">
                            @if($test->questions->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th style="width:50px">#</th>
                                            <th>Вопрос</th>
                                            <th style="width:100px">Тип</th>
                                            <th style="width:80px">Баллы</th>
                                            <th style="width:80px">Ответов</th>
                                            <th style="width:100px">Действия</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($test->questions as $question)
                                        <tr>
                                            <td><span class="badge bg-secondary">{{ $loop->iteration }}</span></td>
                                            <td>
                                                <a href="{{ route('admin.tests.questions.edit', [$test, $question]) }}" class="text-decoration-none">
                                                    {{ Str::limit($question->question_text, 80) }}
                                                </a>
                                                @if($question->is_required)<span class="badge bg-warning ms-1"><i class="bi bi-exclamation-circle"></i></span>@endif
                                            </td>
                                            <td>
                                                @if($question->type === 'single_choice')
                                                    <span class="badge bg-info" title="Один ответ"><i class="bi bi-radio-button"></i></span>
                                                @elseif($question->type === 'multiple_choice')
                                                    <span class="badge bg-success" title="Несколько ответов"><i class="bi bi-check2-square"></i></span>
                                                @elseif($question->type === 'input')
                                                    <span class="badge bg-warning" title="Ввод текста"><i class="bi bi-textarea-resize"></i></span>
                                                @elseif($question->type === 'matching')
                                                    <span class="badge badge-purple" title="Соответствие"><i class="bi bi-arrow-left-right"></i></span>
                                                @endif
                                                {{ $question->type }}
                                            </td>
                                            <td>{{ $question->score }}</td>
                                            <td>{{ $question->answers->count() }}@if($question->matchingPairs->count() > 0) + {{ $question->matchingPairs->count() }} пар@endif</td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="{{ route('admin.tests.questions.edit', [$test, $question]) }}" class="btn btn-outline-warning" title="Редактировать"><i class="bi bi-pencil"></i></a>
                                                    <form action="{{ route('admin.tests.questions.destroy', [$test, $question]) }}" method="POST" class="d-inline" onsubmit="return confirm('Удалить?')">
                                                        @csrf @method('DELETE')
                                                        <button class="btn btn-outline-danger" title="Удалить"><i class="bi bi-trash"></i></button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @else
                            <p class="text-muted mb-0">Нет вопросов</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
@endsection
