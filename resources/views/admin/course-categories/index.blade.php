@extends('layouts.app')
@section('title', 'Категории курсов')
@section('content')
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block sidebar"><div class="position-sticky pt-3">@include('partials._sidebar')</div></nav>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Категории курсов</h1>
                @can('create', App\Models\CourseCategory::class)
                <a href="{{ route('admin.course-categories.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Добавить категорию</a>
                @endcan
            </div>
            @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
            
            <div class="card shadow-sm">
                <div class="card-body">
                    <table class="table table-hover">
                        <thead><tr><th>Название</th><th>Slug</th><th>Курсов</th><th>Статус</th><th>Действия</th></tr></thead>
                        <tbody>
                            @foreach($categories as $category)
                            @include('admin.course-categories._row', ['category' => $category, 'level' => 0])
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>
@endsection
