@extends('layouts.app')
@section('title', 'Мои курсы')
@section('content')
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block sidebar"><div class="position-sticky pt-3">@include('partials._sidebar')</div></nav>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="bi bi-book"></i> Мои курсы</h1>
            </div>
            
            @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
            
            @if($courses->count() > 0)
            <div class="row">
                @foreach($courses as $course)
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card shadow-sm h-100">
                        @if($course->thumbnail)
                        <img src="{{ asset('storage/' . $course->thumbnail) }}" class="card-img-top" alt="{{ $course->title }}" style="height: 200px; object-fit: cover;">
                        @endif
                        <div class="card-body">
                            <h5 class="card-title">{{ $course->title }}</h5>
                            @if($course->category)
                            <span class="badge bg-info mb-2">{{ $course->category->name }}</span>
                            @endif
                            <p class="card-text text-muted">{{ Str::limit($course->description, 100) }}</p>
                        </div>
                        <div class="card-footer bg-white">
                            <a href="{{ route('student.courses.show', $course) }}" class="btn btn-primary btn-sm w-100">
                                <i class="bi bi-list-ol"></i> Открыть содержание
                            </a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> У вас пока нет доступных курсов
            </div>
            @endif
        </main>
    </div>
</div>
@endsection
