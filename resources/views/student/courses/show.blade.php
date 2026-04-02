@extends('layouts.app')
@section('title', $course->title)
@section('content')
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block sidebar"><div class="position-sticky pt-3">@include('partials._sidebar')</div></nav>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">{{ $course->title }}</h1>
                <a href="{{ route('student.courses.index') }}" class="btn btn-secondary btn-sm">Назад</a>
            </div>
            
            <div class="row">
                <div class="col-md-8 mb-4">
                    <div class="card shadow-sm mb-4">
                        <div class="card-body">
                            @if($course->image)
                            <img src="/storage/{{ $course->image }}" class="img-fluid rounded mb-3" alt="{{ $course->title }}">
                            @endif
                            <h4>Описание</h4>
                            <p>{{ $course->description ?? '—' }}</p>
                            
                            @if($course->objectives)
                            <h4>Цели обучения</h4>
                            <p>{{ $course->objectives }}</p>
                            @endif
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white"><h5 class="mb-0">Информация</h5></div>
                        <div class="card-body">
                            @if($course->category)
                            <div><strong>Категория:</strong> {{ $course->category->name }}</div>
                            @endif
                            <div><strong>Тестов:</strong> {{ $course->tests->count() }}</div>
                        </div>
                    </div>
                    
                    @if($course->tests->count() > 0)
                    <div class="card shadow-sm mt-3">
                        <div class="card-header bg-success text-white"><h5 class="mb-0">Тесты</h5></div>
                        <div class="list-group list-group-flush">
                            @foreach($course->tests as $test)
                            <a href="{{ route('student.tests.show', $test) }}" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">{{ $test->title }}</h6>
                                    <small class="text-muted">{{ $test->questions->count() }} вопр.</small>
                                </div>
                            </a>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </main>
    </div>
</div>
@endsection
