@extends('layouts.app')
@section('title', 'Курсы')
@section('content')
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block sidebar"><div class="position-sticky pt-3">@include('partials._sidebar')</div></nav>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Курсы</h1>
                @can('create', App\Models\Course::class)
                <a href="{{ route('admin.courses.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Добавить курс</a>
                @endcan
            </div>
            @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
            
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <form action="{{ route('admin.courses.index') }}" method="GET" class="row g-3">
                        <div class="col-md-4"><input type="text" name="search" class="form-control" placeholder="Поиск..." value="{{ request('search') }}"></div>
                        <div class="col-md-3">
                            <select name="category_id" class="form-select">
                                <option value="">Все категории</option>
                                @foreach($categories as $id => $name)<option value="{{ $id }}" {{ request('category_id') == $id ? 'selected' : '' }}>{{ $name }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="type" class="form-select">
                                <option value="">Все типы</option>
                                <option value="standard" {{ request('type') == 'standard' ? 'selected' : '' }}>Стандартный</option>
                                <option value="scorm" {{ request('type') == 'scorm' ? 'selected' : '' }}>SCORM</option>
                                <option value="h5p" {{ request('type') == 'h5p' ? 'selected' : '' }}>H5P</option>
                            </select>
                        </div>
                        <div class="col-md-2"><button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i></button></div>
                    </form>
                </div>
            </div>
            
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="row">
                        @forelse($courses as $course)
                        <div class="col-md-4 mb-4">
                            <div class="card h-100 shadow-sm">
                                @if($course->thumbnail)
                                <img src="{{ asset('storage/' . $course->thumbnail) }}" class="card-img-top" alt="{{ $course->title }}" style="height:200px;object-fit:cover;">
                                @else
                                <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center" style="height:200px;"><i class="bi bi-book text-white" style="font-size:4rem;"></i></div>
                                @endif
                                <div class="card-body">
                                    <h5 class="card-title">{{ $course->title }}</h5>
                                    <p class="card-text text-muted small">{{ Str::limit($course->description, 80) }}</p>
                                    <div class="mb-2">
                                        <span class="badge bg-info">{{ $course->category?->name ?? 'Без категории' }}</span>
                                        <span class="badge bg-secondary">{{ $course->type }}</span>
                                    </div>
                                    <small class="text-muted">{{ $course->modules->count() }} модулей</small>
                                </div>
                                <div class="card-footer bg-white">
                                    <div class="btn-group btn-group-sm w-100">
                                        <a href="{{ route('admin.courses.show', $course) }}" class="btn btn-outline-primary"><i class="bi bi-eye"></i></a>
                                        @can('update', $course)<a href="{{ route('admin.courses.edit', $course) }}" class="btn btn-outline-warning"><i class="bi bi-pencil"></i></a>@endcan
                                        @can('delete', $course)
                                        <form action="{{ route('admin.courses.destroy', $course) }}" method="POST" class="d-inline" onsubmit="return confirm('Удалить?')">@csrf @method('DELETE')<button class="btn btn-outline-danger"><i class="bi bi-trash"></i></button></form>
                                        @endcan
                                    </div>
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="col-12 text-center text-muted py-5"><i class="bi bi-inbox" style="font-size:3rem;"></i><p class="mt-3">Курсов пока нет</p></div>
                        @endforelse
                    </div>
                </div>
            </div>
            {{ $courses->links() }}
        </main>
    </div>
</div>
@endsection
