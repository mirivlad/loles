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
                            <div><strong>Условия аттестации:</strong> зависят от набора на обучение.</div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info mt-3">
                        Правила сдачи и число попыток устанавливаются для каждого набора.
                        Чтобы пройти этот тест, откройте назначенный экзамен.
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header"><strong>Назначенные экзамены</strong></div>
                        <div class="card-body">
                            @forelse($examRules as $exam)
                            <a class="btn btn-outline-primary w-100 mb-2" href="{{ route('student.exams.show', $exam) }}">
                                {{ $exam->kindLabel() }} — {{ $exam->intake?->name }}
                            </a>
                            @empty
                            <p class="text-muted mb-0">Для ваших наборов этот тест пока не назначен.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

@endsection
