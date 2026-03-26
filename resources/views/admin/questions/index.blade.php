@extends('layouts.app')
@section('title', 'Вопросы теста')
@section('content')
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block sidebar"><div class="position-sticky pt-3">@include('partials._sidebar')</div></nav>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Вопросы: {{ $test->title }}</h1>
                <a href="{{ route('admin.courses.tests.show', [$test->course, $test]) }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Добавить вопрос</a>
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
                                    <span class="badge bg-info">{{ $question->type }}</span>
                                    <strong>#{{ $loop->iteration }}. {{ Str::limit($question->question_text, 100) }}</strong>
                                    <div class="mt-2">
                                        <small class="text-muted">{{ $question->answers->count() }} ответов</small>
                                        @if($question->is_required)<span class="badge bg-warning ms-2">Обязательный</span>@endif
                                        <span class="badge bg-secondary ms-2">{{ $question->score }} балл(а)</span>
                                    </div>
                                </div>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('admin.courses.tests.questions.edit', [$test->course, $test, $question]) }}" class="btn btn-outline-warning"><i class="bi bi-pencil"></i></a>
                                    <form action="{{ route('admin.courses.tests.questions.destroy', [$test->course, $test, $question]) }}" method="POST" class="d-inline" onsubmit="return confirm('Удалить?')">@csrf @method('DELETE')<button class="btn btn-outline-danger"><i class="bi bi-trash"></i></button></form>
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
@endsection
