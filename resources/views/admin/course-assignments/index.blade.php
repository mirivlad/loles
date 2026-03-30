@extends('layouts.app')
@section('title', 'Назначения курсов')
@section('content')
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block sidebar"><div class="position-sticky pt-3">@include('partials._sidebar')</div></nav>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Назначения курсов</h1>
                <a href="{{ route('admin.course-assignments.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Добавить назначение</a>
            </div>
            @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
            
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <form action="{{ route('admin.course-assignments.index') }}" method="GET" class="row g-3">
                        <div class="col-md-6">
                            <select name="course_id" class="form-select">
                                <option value="">Все курсы</option>
                                @foreach($courses as $id => $title)
                                <option value="{{ $id }}" {{ request('course_id') == $id ? 'selected' : '' }}>{{ $title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <select name="type" class="form-select">
                                <option value="">Все типы</option>
                                <option value="individual">Индивидуальное</option>
                                <option value="group">Группе</option>
                                <option value="organization">Организации</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i></button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Курс</th>
                                    <th>Тип</th>
                                    <th>Назначено</th>
                                    <th>Период</th>
                                    <th>Статус</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($assignments as $assignment)
                                <tr>
                                    <td><strong>{{ $assignment->course->title }}</strong></td>
                                    <td>
                                        @if($assignment->type === 'individual')
                                            <span class="badge bg-success"><i class="bi bi-person"></i> Индивидуально</span>
                                        @elseif($assignment->type === 'group')
                                            <span class="badge bg-info"><i class="bi bi-people"></i> Группе</span>
                                        @else
                                            <span class="badge bg-primary"><i class="bi bi-building"></i> Организации</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($assignment->type === 'individual')
                                            {{ $assignment->user?->name ?? '—' }}
                                        @elseif($assignment->type === 'group')
                                            {{ $assignment->group?->name ?? '—' }}
                                        @else
                                            {{ $assignment->organization?->name ?? '—' }}
                                        @endif
                                    </td>
                                    <td>
                                        <small>{{ $assignment->start_date->format('d.m.Y') }}</small>
                                        @if($assignment->end_date)
                                        <br><small>→ {{ $assignment->end_date->format('d.m.Y') }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($assignment->is_active)
                                            <span class="badge bg-success">Активно</span>
                                        @else
                                            <span class="badge bg-secondary">Не активно</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('admin.course-assignments.show', $assignment) }}" class="btn btn-outline-primary" title="Просмотр"><i class="bi bi-eye"></i></a>
                                            <a href="{{ route('admin.course-assignments.edit', $assignment) }}" class="btn btn-outline-warning" title="Редактировать"><i class="bi bi-pencil"></i></a>
                                            <form action="{{ route('admin.course-assignments.destroy', $assignment) }}" method="POST" class="d-inline" onsubmit="return confirm('Удалить?')">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-outline-danger" title="Удалить"><i class="bi bi-trash"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-5">
                                        <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                                        <p class="mt-3">Назначений пока нет</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            {{ $assignments->links() }}
        </main>
    </div>
</div>
@endsection
