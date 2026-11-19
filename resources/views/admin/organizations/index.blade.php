@extends('layouts.app')
@section('title', 'Предприятия')
@section('content')
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
            <div class="position-sticky pt-3">@include('partials._sidebar')</div>
        </nav>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between flex-wrap align-items-center gap-2 pt-3 pb-3 mb-3 border-bottom">
                <div>
                    <h1 class="h2 mb-1">Предприятия</h1>
                    <p class="text-secondary mb-0">Организации, учебные группы и учащиеся</p>
                </div>
                @can('create', App\Models\Organization::class)
                    <a href="{{ route('admin.organizations.create') }}" class="btn btn-primary"><i class="bi bi-building-add me-1"></i> Добавить предприятие</a>
                @endcan
            </div>
            @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="alert alert-danger" role="alert">{{ session('error') }}</div>@endif
            <div class="row g-3 mb-4">
                <div class="col-md-4"><div class="manager-summary h-100"><div class="manager-summary__value">{{ number_format($totalOrganizations, 0, ',', ' ') }}</div><div class="manager-summary__label">Всего предприятий</div></div></div>
                <div class="col-md-4"><div class="manager-summary h-100"><div class="manager-summary__value">{{ number_format($activeOrganizations, 0, ',', ' ') }}</div><div class="manager-summary__label">Активных</div></div></div>
                <div class="col-md-4"><div class="manager-summary h-100"><div class="manager-summary__value">{{ number_format($totalCompanyUsers, 0, ',', ' ') }}</div><div class="manager-summary__label">Прикреплено пользователей</div></div></div>
            </div>
            <form method="GET" action="{{ route('admin.organizations.index') }}" class="manager-toolbar mb-3" aria-label="Фильтры предприятий">
                <div class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <label for="organizationSearch" class="form-label">Название, ИНН или email</label>
                        <input type="search" id="organizationSearch" name="search" value="{{ request('search') }}" class="form-control" placeholder="Найти предприятие…">
                    </div>
                    <div class="col-md-3">
                        <label for="organizationStatus" class="form-label">Статус</label>
                        <select name="status" id="organizationStatus" class="form-select">
                            <option value="">Любой</option>
                            <option value="active" @selected(request('status') === 'active')>Активно</option>
                            <option value="inactive" @selected(request('status') === 'inactive')>Неактивно</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-search"></i> Найти</button>
                        <a href="{{ route('admin.organizations.index') }}" class="btn btn-outline-secondary" title="Сбросить фильтры" aria-label="Сбросить фильтры"><i class="bi bi-x-lg"></i></a>
                    </div>
                </div>
            </form>
            <p class="small text-secondary">Найдено предприятий: {{ $organizations->total() }}</p>
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover manager-table align-middle mb-0">
                            <thead class="table-light"><tr>
                                <th class="ps-3">Предприятие</th>
                                <th>Контакты</th>
                                <th>Учащиеся</th>
                                <th>Группы</th>
                                <th>Статус</th>
                                <th class="text-end pe-3">Действия</th>
                            </tr></thead>
                            <tbody>
                                @forelse($organizations as $org)
                                    <tr>
                                        <td class="ps-3">
                                            <a class="fw-semibold text-decoration-none" href="{{ route('admin.organizations.show', $org) }}">{{ $org->name }}</a>
                                            @if($org->inn)<div class="small text-secondary">ИНН: {{ $org->inn }}@if($org->kpp) · КПП: {{ $org->kpp }}@endif</div>@endif
                                        </td>
                                        <td>
                                            @if($org->email)<div class="small">{{ $org->email }}</div>@endif
                                            @if($org->phone)<div class="small text-secondary">{{ $org->phone }}</div>@endif
                                            @if(!$org->email && !$org->phone)<span class="text-secondary">—</span>@endif
                                        </td>
                                        <td><a href="{{ route('admin.users.index', ['organization_id' => $org->id]) }}" title="Посмотреть сотрудников">{{ $org->users_count }}</a></td>
                                        <td>{{ $org->groups_count }}</td>
                                        <td><span class="badge {{ $org->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $org->is_active ? 'Активно' : 'Неактивно' }}</span></td>
                                        <td class="text-end pe-3">
                                            <div class="btn-group btn-group-sm" role="group" aria-label="Действия для {{ $org->name }}">
                                                <a href="{{ route('admin.users.index', ['organization_id' => $org->id]) }}" class="btn btn-outline-primary" title="Сотрудники" aria-label="Сотрудники {{ $org->name }}"><i class="bi bi-people"></i></a>
                                                <a href="{{ route('admin.organizations.show', $org) }}" class="btn btn-outline-primary" title="Карточка предприятия" aria-label="Открыть {{ $org->name }}"><i class="bi bi-eye"></i></a>
                                                @can('update', $org)
                                                    <a href="{{ route('admin.organizations.edit', $org) }}" class="btn btn-outline-secondary" title="Изменить" aria-label="Изменить {{ $org->name }}"><i class="bi bi-pencil"></i></a>
                                                @endcan
                                                @can('delete', $org)
                                                    <form action="{{ route('admin.organizations.destroy', $org) }}" method="POST" onsubmit="return confirm('Удалить предприятие?')">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="btn btn-outline-danger" title="Удалить" aria-label="Удалить {{ $org->name }}"><i class="bi bi-trash"></i></button>
                                                    </form>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6"><div class="manager-empty">
                                        <i class="bi bi-buildings fs-2 d-block mb-2"></i>
                                        <strong>Предприятий не найдено</strong>
                                        <p class="mb-2">Измените фильтры или создайте новое предприятие.</p>
                                        <a href="{{ route('admin.organizations.index') }}">Очистить фильтры</a>
                                    </div></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="mt-3">{{ $organizations->links() }}</div>
        </main>
    </div>
</div>
@endsection
