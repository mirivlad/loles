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
                            <div><strong>Время:</strong> <span class="text-muted">Не ограничено</span></div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info mt-3">
                        <i class="bi bi-info-circle"></i> 
                        <strong>Важно!</strong> Вопросы будут показаны только после начала тестирования. 
                        У вас будет одна попытка для прохождения теста.
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
                    
                    <div class="card shadow-sm mt-3">
                        <div class="card-header"><h6 class="mb-0">Попытки</h6></div>
                        <div class="card-body">
                            <p class="text-muted mb-0">Попыток пока не было</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

@push('scripts')
<script>
function startTest() {
    if (confirm('Вы готовы начать тестирование? Вопросы будут показаны по одному.')) {
        alert('Функционал прохождения тестов будет реализован в следующем этапе');
    }
}
</script>
@endpush
@endsection
