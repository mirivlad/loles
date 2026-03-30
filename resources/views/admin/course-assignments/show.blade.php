@extends('layouts.app')
@section('title', 'Назначение курса')
@section('content')
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block sidebar"><div class="position-sticky pt-3">@include('partials._sidebar')</div></nav>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Назначение курса</h1>
                <div>
                    <a href="{{ route('admin.course-assignments.edit', $assignment) }}" class="btn btn-warning btn-sm me-2">Редактировать</a>
                    <a href="{{ route('admin.course-assignments.index') }}" class="btn btn-secondary btn-sm">Назад</a>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-8 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white"><h5 class="mb-0">Информация</h5></div>
                        <div class="card-body">
                            <table class="table table-sm">
                                <tr>
                                    <th width="40%">Курс:</th>
                                    <td><strong>{{ $assignment->course->title }}</strong></td>
                                </tr>
                                <tr>
                                    <th>Тип:</th>
                                    <td>
                                        @if($assignment->type === 'individual')
                                            <span class="badge bg-info"><i class="bi bi-person"></i> Индивидуально</span>
                                        @elseif($assignment->type === 'group')
                                            <span class="badge bg-success"><i class="bi bi-people"></i> Группе</span>
                                        @else
                                            <span class="badge bg-primary"><i class="bi bi-building"></i> Организации</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Назначено:</th>
                                    <td>
                                        @if($assignment->type === 'individual')
                                            {{ $assignment->user?->name ?? '—' }}
                                        @elseif($assignment->type === 'group')
                                            {{ $assignment->group?->name ?? '—' }}
                                        @else
                                            {{ $assignment->organization?->name ?? '—' }}
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Заметка:</th>
                                    <td>{{ $assignment->note ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <th>Создано:</th>
                                    <td>{{ $assignment->creator?->name ?? '—' }}, {{ $assignment->created_at->format('d.m.Y H:i') }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-success text-white"><h5 class="mb-0">Период доступа</h5></div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="text-muted small">Дата начала</label>
                                <div class="fs-5">{{ $assignment->start_date->format('d.m.Y') }}</div>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small">Дата окончания</label>
                                <div class="fs-5">
                                    @if($assignment->end_date)
                                        {{ $assignment->end_date->format('d.m.Y') }}
                                    @else
                                        <span class="text-muted">Бессрочно</span>
                                    @endif
                                </div>
                            </div>
                            <hr>
                            <div>
                                <label class="text-muted small">Статус</label>
                                @if($assignment->is_active)
                                    <span class="badge bg-success">Активно</span>
                                @else
                                    <span class="badge bg-secondary">Не активно</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
@endsection
