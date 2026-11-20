@extends('layouts.app')
@section('title', 'Отчёты по назначенному обучению')
@section('content')
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block sidebar">
            <div class="position-sticky pt-3">@include('partials._sidebar')</div>
        </nav>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                <div>
                    <h1 class="h2 mb-1">Отчёты по обучению</h1>
                    <p class="text-secondary mb-0">
                        Кому, что и на какой период назначено. Подробные результаты прохождения
                        появятся после подключения системы фиксации обучения.
                    </p>
                </div>
                <a href="{{ route('admin.reports.export', request()->except('page')) }}"
                   class="btn btn-outline-primary">
                    <i class="bi bi-filetype-csv me-1"></i> Скачать CSV
                </a>
            </div>

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.reports.index') }}" class="row g-3 align-items-end">
                        <div class="col-lg-5">
                            <label for="reportSearch" class="form-label">Курс, группа, учащийся или предприятие</label>
                            <input id="reportSearch" type="search" name="q" class="form-control"
                                   value="{{ request('q') }}" placeholder="Начните вводить название">
                        </div>
                        <div class="col-lg-3">
                            <label for="reportStatus" class="form-label">Состояние назначения</label>
                            <select id="reportStatus" name="status" class="form-select">
                                <option value="">Все состояния</option>
                                <option value="active" @selected(request('status') === 'active')>Доступно сейчас</option>
                                <option value="upcoming" @selected(request('status') === 'upcoming')>Начнётся позднее</option>
                                <option value="expired" @selected(request('status') === 'expired')>Срок истёк</option>
                                <option value="disabled" @selected(request('status') === 'disabled')>Отключено</option>
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <label for="reportStart" class="form-label">Начало с</label>
                            <input id="reportStart" type="date" name="from" class="form-control"
                                   value="{{ request('from') }}">
                        </div>
                        <div class="col-lg-2">
                            <label for="reportEnd" class="form-label">Начало по</label>
                            <input id="reportEnd" type="date" name="to" class="form-control"
                                   value="{{ request('to') }}">
                        </div>
                        <div class="col-12 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search me-1"></i> Показать
                            </button>
                            <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-secondary">
                                Очистить фильтры
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="text-secondary">
                    Найдено назначений: <strong>{{ $assignments->total() }}</strong>
                </div>
                <div class="text-secondary small">
                    Страница {{ $assignments->currentPage() }} из {{ max($assignments->lastPage(), 1) }}
                </div>
            </div>
            <div class="card border-0 shadow-sm mb-3">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                        <tr>
                            <th>Курс</th>
                            <th>Кому назначено</th>
                            <th>Период</th>
                            <th>Состояние</th>
                            <th>Просмотр</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($assignments as $assignment)
                            @php
                                $recipient = match($assignment->type) {
                                    'individual' => $assignment->user?->name ?? 'Удалённый пользователь',
                                    'group' => $assignment->group?->name ?? 'Удалённая группа',
                                    'organization' => $assignment->organization?->name ?? 'Удалённое предприятие',
                                    default => 'Неизвестный получатель',
                                };
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $assignment->course?->title ?? 'Курс удалён' }}</strong>
                                </td>
                                <td>
                                    <div>{{ $recipient }}</div>
                                    <small class="text-secondary">{{ $assignment->typeLabel() }}</small>
                                </td>
                                <td class="small">
                                    {{ $assignment->start_date?->format('d.m.Y') ?? '—' }}
                                    @if($assignment->end_date)
                                        <span class="text-secondary">—</span> {{ $assignment->end_date->format('d.m.Y') }}
                                    @else
                                        <span class="text-secondary">· бессрочно</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $assignment->statusColorClass() }}">
                                        {{ $assignment->statusLabel() }}
                                    </span>
                                </td>
                                <td>
                                    @if($assignment->course)
                                        <a class="btn btn-sm btn-outline-primary"
                                           href="{{ route('admin.course-assignments.show', [
                                               'course' => $assignment->course_id,
                                               'start' => $assignment->start_date?->format('Y-m-d'),
                                               'end' => $assignment->end_date?->format('Y-m-d') ?? 'null',
                                           ]) }}">
                                            Открыть
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-5 text-center text-secondary">
                                    Назначений по этим условиям не найдено.
                                    Попробуйте изменить фильтры.
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            {{ $assignments->links() }}
        </main>
    </div>
</div>
@endsection
