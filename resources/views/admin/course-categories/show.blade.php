@extends('layouts.app')
@section('title', $category->name)
@section('content')
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block sidebar"><div class="position-sticky pt-3">@include('partials._sidebar')</div></nav>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">{{ $category->name }}</h1>
                <div>
                    @can('update', $category)<a href="{{ route('admin.course-categories.edit', $category) }}" class="btn btn-warning btn-sm me-2">Редактировать</a>@endcan
                    <a href="{{ route('admin.course-categories.index') }}" class="btn btn-secondary btn-sm">Назад</a>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white"><h5 class="mb-0">Информация</h5></div>
                        <div class="card-body">
                            <table class="table table-sm">
                                <tr><th>Название:</th><td>{{ $category->name }}</td></tr>
                                <tr><th>Slug:</th><td><code>{{ $category->slug }}</code></td></tr>
                                <tr><th>Описание:</th><td>{{ $category->description ?? '—' }}</td></tr>
                                <tr><th>Родитель:</th><td>{{ $category->parent?->name ?? '—' }}</td></tr>
                                <tr><th>Порядок:</th><td>{{ $category->sort_order }}</td></tr>
                                <tr><th>Статус:</th><td>@if($category->is_active)<span class="badge bg-success">Активна</span>@else<span class="badge bg-secondary">Не активна</span>@endif</td></tr>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-success text-white"><h5 class="mb-0">Статистика</h5></div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-6 mb-3">
                                    <div class="display-4 text-primary">{{ $category->courses->count() }}</div>
                                    <div class="text-muted">Курсов</div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="display-4 text-info">{{ $category->children->count() }}</div>
                                    <div class="text-muted">Подкатегорий</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @if($category->children->count() > 0)
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header"><h5 class="mb-0"><i class="bi bi-folder"></i> Подкатегории</h5></div>
                        <div class="card-body">
                            <ul class="list-group">
                                @foreach($category->children as $child)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <a href="{{ route('admin.course-categories.show', $child) }}">{{ $child->name }}</a>
                                    <span class="badge bg-secondary">{{ $child->courses_count }} курсов</span>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            @endif
            @if($category->courses->count() > 0)
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header"><h5 class="mb-0"><i class="bi bi-book"></i> Курсы</h5></div>
                        <div class="card-body">
                            <ul class="list-group">
                                @foreach($category->courses->take(10) as $course)
                                <li class="list-group-item">{{ $course->title }}</li>
                                @endforeach
                            </ul>
                            @if($category->courses->count() > 10)
                            <div class="mt-2 text-muted small">+ ещё {{ $category->courses->count() - 10 }} курсов</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </main>
    </div>
</div>
@endsection
