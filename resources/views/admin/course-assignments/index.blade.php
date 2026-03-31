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
                        <div class="col-md-8">
                            <select name="course_id" class="form-select">
                                <option value="">Все курсы</option>
                                @foreach($courses as $id => $title)
                                <option value="{{ $id }}" {{ request('course_id') == $id ? 'selected' : '' }}>{{ $title }}</option>
                                @endforeach
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
                                    <th>Период</th>
                                    <th class="text-center"><i class="bi bi-person" title="Индивидуальные"></i></th>
                                    <th class="text-center"><i class="bi bi-people" title="Группы"></i></th>
                                    <th class="text-center"><i class="bi bi-building" title="Организации"></i></th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($assignments as $group)
                                <tr>
                                    <td>
                                        <strong>{{ $group->course->title }}</strong>
                                    </td>
                                    <td>
                                        <small>{{ $group->start_date->format('d.m.Y') }}</small>
                                        @if($group->end_date)
                                        <br><small>→ {{ $group->end_date->format('d.m.Y') }}</small>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($group->individual_count > 0)
                                        <span class="badge bg-success">{{ $group->individual_count }}</span>
                                        @else
                                        <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($group->group_count > 0)
                                        <span class="badge bg-info">{{ $group->group_count }}</span>
                                        @else
                                        <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($group->organization_count > 0)
                                        <span class="badge bg-primary">{{ $group->organization_count }}</span>
                                        @else
                                        <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('admin.course-assignments.show', ['course' => $group->course_id, 'start' => $group->start_date->format('Y-m-d'), 'end' => $group->end_date?->format('Y-m-d') ?? 'null']) }}" class="btn btn-outline-primary" title="Просмотр">
                                                <i class="bi bi-eye"></i>
                                            </a>
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
