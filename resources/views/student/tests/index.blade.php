@extends('layouts.app')
@section('title', 'Тесты')
@section('content')
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block sidebar"><div class="position-sticky pt-3">@include('partials._sidebar')</div></nav>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="bi bi-file-earmark-text"></i> Тесты</h1>
                <a class="btn btn-primary" href="{{ route('student.exams.index') }}">Мои экзамены</a>
            </div>
            
            @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
            
            @if($tests->count() > 0)
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Название</th>
                                    <th>Курс</th>
                                    <th>Вопросов</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tests as $test)
                                <tr>
                                    <td><strong>{{ $test->title }}</strong></td>
                                    <td>{{ $test->course->title }}</td>
                                    <td><span class="badge bg-info">{{ $test->questions->count() }}</span></td>
                                    <td>
                                        <a href="{{ route('student.tests.show', $test) }}" class="btn btn-primary btn-sm">
                                            <i class="bi bi-play"></i> Подробнее
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @else
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> У вас пока нет доступных тестов
            </div>
            @endif
        </main>
    </div>
</div>
@endsection
