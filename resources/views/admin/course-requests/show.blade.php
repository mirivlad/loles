@extends('layouts.app')
@section('title', 'Заявка #' . $courseRequest->id)
@section('content')
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block sidebar"><div class="position-sticky pt-3">@include('partials._sidebar')</div></nav>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Заявка #{{ $courseRequest->id }}</h1>
                <div>
                    @if($courseRequest->isPending())
                    <form action="{{ route('admin.course-requests.approve', $courseRequest) }}" method="POST" class="d-inline">
                        @csrf
                        <button class="btn btn-success btn-sm"><i class="bi bi-check-lg"></i> Одобрить</button>
                    </form>
                    <form action="{{ route('admin.course-requests.reject', $courseRequest) }}" method="POST" class="d-inline">
                        @csrf
                        <button class="btn btn-danger btn-sm"><i class="bi bi-x-lg"></i> Отклонить</button>
                    </form>
                    <a href="{{ route('admin.course-requests.edit', $courseRequest) }}" class="btn btn-warning btn-sm"><i class="bi bi-pencil"></i> Редактировать</a>
                    @endif
                    <a href="{{ route('admin.course-requests.index') }}" class="btn btn-secondary btn-sm">Назад</a>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white"><h5 class="mb-0">Информация</h5></div>
                        <div class="card-body">
                            <div><strong>Организация:</strong> {{ $courseRequest->organization->name }}</div>
                            <div><strong>Статус:</strong>
                                @if($courseRequest->isPending())
                                <span class="badge bg-warning">Ожидает</span>
                                @elseif($courseRequest->isApproved())
                                <span class="badge bg-success">Одобрена</span>
                                @else
                                <span class="badge bg-danger">Отклонена</span>
                                @endif
                            </div>
                            <div><strong>Создана:</strong> {{ $courseRequest->created_at->format('d.m.Y H:i') }}</div>
                            <div><strong>Кем:</strong> {{ $courseRequest->requestedBy->name }}</div>
                            @if($courseRequest->approved_by)
                            <div><strong>{{ $courseRequest->isApproved() ? 'Одобрена' : 'Отклонена' }}:</strong> {{ $courseRequest->approvedBy?->name }} {{ $courseRequest->approved_at->format('d.m.Y H:i') }}</div>
                            @endif
                            @if($courseRequest->note)
                            <div class="mt-3"><strong>Заметка:</strong><br>{{ $courseRequest->note }}</div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-header bg-success text-white"><h5 class="mb-0">Статистика</h5></div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-4">
                                    <div class="display-6 text-primary">{{ $courseRequest->items->count() }}</div>
                                    <div class="text-muted">Курсов</div>
                                </div>
                                <div class="col-4">
                                    <div class="display-6 text-success">{{ $courseRequest->items->where('user_id')->count() }}</div>
                                    <div class="text-muted">Пользователей</div>
                                </div>
                                <div class="col-4">
                                    <div class="display-6 text-info">{{ $courseRequest->items->where('group_id')->count() }}</div>
                                    <div class="text-muted">Групп</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card shadow-sm">
                <div class="card-header"><h5 class="mb-0">Элементы заявки</h5></div>
                <div class="card-body">
                    @if($courseRequest->items->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Курс</th>
                                    <th>Тип</th>
                                    <th>Получатель</th>
                                    <th>Период</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($courseRequest->items as $item)
                                <tr>
                                    <td><strong>{{ $item->course->title }}</strong></td>
                                    <td>
                                        @if($item->user_id)
                                        <span class="badge bg-success"><i class="bi bi-person"></i> Индивидуально</span>
                                        @elseif($item->group_id)
                                        <span class="badge bg-info"><i class="bi bi-people"></i> Группе</span>
                                        @elseif($item->organization_id)
                                        <span class="badge bg-primary"><i class="bi bi-building"></i> Организации</span>
                                        @else
                                        <span class="badge bg-secondary"><i class="bi bi-building"></i> Организации заявки</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($item->user_id)
                                        {{ $item->user?->name ?? '—' }}
                                        @elseif($item->group_id)
                                        {{ $item->group?->name ?? '—' }}
                                        @elseif($item->organization_id)
                                        {{ $item->organization?->name ?? '—' }}
                                        @else
                                        {{ $courseRequest->organization->name }}
                                        @endif
                                    </td>
                                    <td>
                                        <small>{{ $item->start_date->format('d.m.Y') }}</small>
                                        @if($item->end_date)
                                        <br><small>→ {{ $item->end_date->format('d.m.Y') }}</small>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="text-muted text-center py-4">В заявке нет элементов</p>
                    @endif
                </div>
            </div>
            
            @if($courseRequest->isApproved())
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-success text-white"><h5 class="mb-0"><i class="bi bi-check-circle"></i> Назначения созданы</h5></div>
                <div class="card-body">
                    <p class="mb-0">По этой заявке были созданы назначения. Проверить их можно в разделе <a href="{{ route('admin.course-assignments.index') }}">Назначения курсов</a>.</p>
                </div>
            </div>
            @endif
        </main>
    </div>
</div>
@endsection
