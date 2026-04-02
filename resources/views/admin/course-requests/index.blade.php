@extends('layouts.app')
@section('title', 'Заявки на курсы')
@section('content')
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block sidebar"><div class="position-sticky pt-3">@include('partials._sidebar')</div></nav>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Заявки на курсы</h1>
                <a href="{{ route('admin.course-requests.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Создать заявку</a>
            </div>
            @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
            
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <form action="{{ route('admin.course-requests.index') }}" method="GET" class="row g-3">
                        <div class="col-md-4">
                            <select name="status" class="form-select">
                                <option value="">Все статусы</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Ожидают</option>
                                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Одобренные</option>
                                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Отклонённые</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <select name="organization_id" class="form-select">
                                <option value="">Все организации</option>
                                @foreach($organizations as $id => $name)
                                <option value="{{ $id }}" {{ request('organization_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
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
                                    <th>№</th>
                                    <th>Статус</th>
                                    <th>Курсов</th>
                                    <th>Получатели</th>
                                    <th>Создана</th>
                                    <th>Кем</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($requests as $request)
                                <tr>
                                    <td><strong>#{{ $request->id }}</strong></td>
                                    <td>
                                        @if($request->isPending())
                                        <span class="badge bg-warning">Ожидает</span>
                                        @elseif($request->isApproved())
                                        <span class="badge bg-success">Одобрена</span>
                                        @else
                                        <span class="badge bg-danger">Отклонена</span>
                                        @endif
                                    </td>
                                    <td><span class="badge bg-info">{{ $request->items->count() }}</span></td>
                                    <td>
                                        @php
                                            $recipients = [];
                                            if ($request->items->where('user_id')->count() > 0) {
                                                $recipients[] = '<span class="badge bg-success"><i class="bi bi-person"></i> ' . $request->items->where('user_id')->count() . '</span>';
                                            }
                                            if ($request->items->where('group_id')->count() > 0) {
                                                $recipients[] = '<span class="badge bg-info"><i class="bi bi-people"></i> ' . $request->items->where('group_id')->count() . '</span>';
                                            }
                                            if ($request->items->where('organization_id')->count() > 0) {
                                                $recipients[] = '<span class="badge bg-primary"><i class="bi bi-building"></i> ' . $request->items->where('organization_id')->count() . '</span>';
                                            }
                                            if (empty($recipients)) {
                                                $recipients[] = '<span class="text-muted">—</span>';
                                            }
                                        @endphp
                                        {!! implode(' ', $recipients) !!}
                                    </td>
                                    <td><small>{{ $request->created_at->format('d.m.Y H:i') }}</small></td>
                                    <td>{{ $request->requestedBy?->name ?? 'Удалён' }}</td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('admin.course-requests.show', $request) }}" class="btn btn-outline-primary" title="Просмотр"><i class="bi bi-eye"></i></a>
                                            @if($request->isPending())
                                            <a href="{{ route('admin.course-requests.edit', $request) }}" class="btn btn-outline-warning" title="Редактировать"><i class="bi bi-pencil"></i></a>
                                            <form action="{{ route('admin.course-requests.approve', $request) }}" method="POST" class="d-inline" title="Одобрить">
                                                @csrf
                                                <button class="btn btn-outline-success" onclick="return confirm('Одобрить заявку?')"><i class="bi bi-check-lg"></i></button>
                                            </form>
                                            <form action="{{ route('admin.course-requests.reject', $request) }}" method="POST" class="d-inline" title="Отклонить">
                                                @csrf
                                                <button class="btn btn-outline-danger" onclick="return confirm('Отклонить заявку?')"><i class="bi bi-x-lg"></i></button>
                                            </form>
                                            <form action="{{ route('admin.course-requests.destroy', $request) }}" method="POST" class="d-inline" onsubmit="return confirm('Удалить заявку?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-outline-danger" title="Удалить"><i class="bi bi-trash"></i></button>
                                            </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-5">
                                        <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                                        <p class="mt-3">Заявок пока нет</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            {{ $requests->links() }}
        </main>
    </div>
</div>
@endsection
