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
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header d-flex justify-content-between">
                            <h5 class="mb-0">Вопросы</h5>
                            <a href="#" class="btn btn-sm btn-primary"><i class="bi bi-plus"></i></a>
                        </div>
                        <div class="card-body">
                            @if($test->questions->count() > 0)
                            <ul class="list-group list-group-flush">
                                @foreach($test->questions as $question)
                                <li class="list-group-item d-flex justify-content-between align-items-start">
                                    <div class="ms-2 me-auto">
                                        <div class="fw-bold">{{ $question->type }}</div>
                                        {{ Str::limit($question->question_text, 50) }}
                                    </div>
                                    <span class="badge bg-primary rounded-pill">{{ $question->answers->count() }}</span>
                                </li>
                                @endforeach
                            </ul>
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
