@extends('layouts.app')
@section('title', 'Назначение: ' . $course->title)
@section('content')
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block sidebar"><div class="position-sticky pt-3">@include('partials._sidebar')</div></nav>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">{{ $course->title }}</h1>
                <a href="{{ route('admin.course-assignments.index') }}" class="btn btn-secondary btn-sm">Назад</a>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white"><h5 class="mb-0">Период</h5></div>
                        <div class="card-body">
                            <div><strong>Начало:</strong> {{ \Carbon\Carbon::parse($start_date)->format('d.m.Y') }}</div>
                            <div><strong>Окончание:</strong> {{ $end_date ? \Carbon\Carbon::parse($end_date)->format('d.m.Y') : 'Бессрочно' }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card shadow-sm">
                        <div class="card-header bg-success text-white"><h5 class="mb-0">Статистика</h5></div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-4">
                                    <div class="display-6 text-success">{{ $individual->count() }}</div>
                                    <div class="text-muted"><i class="bi bi-person"></i> Пользователей</div>
                                </div>
                                <div class="col-4">
                                    <div class="display-6 text-info">{{ $groups->count() }}</div>
                                    <div class="text-muted"><i class="bi bi-people"></i> Групп</div>
                                </div>
                                <div class="col-4">
                                    <div class="display-6 text-primary">{{ $organizations->count() }}</div>
                                    <div class="text-muted"><i class="bi bi-building"></i> Организаций</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bi bi-person"></i> Пользователи</h5>
                            <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#addUserModal"><i class="bi bi-plus-lg"></i></button>
                        </div>
                        <div class="card-body p-0">
                            @if($individual->count() > 0)
                            <ul class="list-group list-group-flush">
                                @foreach($individual as $a)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <a href="{{ route('admin.users.show', $a->user) }}">{{ $a->user?->name ?? '—' }}</a>
                                    <form action="{{ route('admin.course-assignments.destroy', $a) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-lg"></i></button>
                                    </form>
                                </li>
                                @endforeach
                            </ul>
                            @else
                            <p class="text-muted text-center py-4">Нет пользователей</p>
                            @endif
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bi bi-people"></i> Группы</h5>
                            <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#addGroupModal"><i class="bi bi-plus-lg"></i></button>
                        </div>
                        <div class="card-body p-0">
                            @if($groups->count() > 0)
                            <ul class="list-group list-group-flush">
                                @foreach($groups as $a)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <a href="{{ route('admin.groups.show', $a->group) }}">{{ $a->group?->name ?? '—' }}</a>
                                    <form action="{{ route('admin.course-assignments.destroy', $a) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-lg"></i></button>
                                    </form>
                                </li>
                                @endforeach
                            </ul>
                            @else
                            <p class="text-muted text-center py-4">Нет групп</p>
                            @endif
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bi bi-building"></i> Организации</h5>
                            <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#addOrganizationModal"><i class="bi bi-plus-lg"></i></button>
                        </div>
                        <div class="card-body p-0">
                            @if($organizations->count() > 0)
                            <ul class="list-group list-group-flush">
                                @foreach($organizations as $a)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <a href="{{ route('admin.organizations.show', $a->organization) }}">{{ $a->organization?->name ?? '—' }}</a>
                                    <form action="{{ route('admin.course-assignments.destroy', $a) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-lg"></i></button>
                                    </form>
                                </li>
                                @endforeach
                            </ul>
                            @else
                            <p class="text-muted text-center py-4">Нет организаций</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal добавления пользователя -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.course-assignments.store') }}" method="POST">
                @csrf
                <input type="hidden" name="course_ids" value="{{ $course->id }}">
                <input type="hidden" name="start_date" value="{{ $start_date }}">
                <input type="hidden" name="end_date" value="{{ $end_date }}">
                <div class="modal-header">
                    <h5 class="modal-title">Добавить пользователя</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <x-tags-input name="user_ids" url="{{ route('api.users.search') }}" placeholder="Начните вводить имя..." badge_color="success" />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-success">Добавить</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal добавления группы -->
<div class="modal fade" id="addGroupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.course-assignments.store') }}" method="POST">
                @csrf
                <input type="hidden" name="course_ids" value="{{ $course->id }}">
                <input type="hidden" name="start_date" value="{{ $start_date }}">
                <input type="hidden" name="end_date" value="{{ $end_date }}">
                <div class="modal-header">
                    <h5 class="modal-title">Добавить группу</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <x-tags-input name="group_ids" url="{{ route('api.groups.search') }}" placeholder="Начните вводить название..." badge_color="info" />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-info">Добавить</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal добавления организации -->
<div class="modal fade" id="addOrganizationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.course-assignments.store') }}" method="POST">
                @csrf
                <input type="hidden" name="course_ids" value="{{ $course->id }}">
                <input type="hidden" name="start_date" value="{{ $start_date }}">
                <input type="hidden" name="end_date" value="{{ $end_date }}">
                <div class="modal-header">
                    <h5 class="modal-title">Добавить организацию</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <x-tags-input name="organization_ids" url="{{ route('api.organizations.search') }}" placeholder="Начните вводить название..." badge_color="primary" />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">Добавить</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
