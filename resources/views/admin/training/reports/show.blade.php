@extends('layouts.app')
@section('title', 'Ведомость: ' . $intake->name)
@section('content')
<div class="container-fluid"><div class="row">
<nav class="col-md-3 col-lg-2 d-md-block sidebar"><div class="position-sticky pt-3">@include('partials._sidebar')</div></nav>
<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
<div><h1 class="h2 mb-1">Ведомость результатов</h1><p class="text-muted mb-0">{{ $intake->name }} — {{ $intake->program->name }}</p></div>
<div class="d-flex flex-wrap gap-2"><a class="btn btn-outline-secondary" href="{{ route('admin.training.intakes.show', $intake) }}">Назад к набору</a>
<a class="btn btn-primary" href="{{ route('admin.training.reports.export', array_merge(['intake' => $intake->id], request()->except('page'))) }}">Выгрузить CSV</a></div></div>
<div class="alert alert-info">
Итог вычисляется по обязательным официальным экзаменам и подтверждённому изучению каждого курса.
Пробные и аннулированные попытки не учитываются. Отметки материалов учащиеся делают самостоятельно;
они означают подтверждённый просмотр, а не независимую проверку знаний. Свидетельства здесь не выдаются.
</div>
<div class="card shadow-sm mb-4"><div class="card-body">
<form method="GET" action="{{ route('admin.training.reports.show', $intake) }}" class="row g-3 align-items-end">
<div class="col-md-3"><label class="form-label">Заявитель</label>
<select name="scope" class="form-select">
<option value="all" @selected(($filter['scope'] ?? 'all') === 'all')>Все</option>
<option value="enterprise" @selected(($filter['scope'] ?? null) === 'enterprise')>Предприятия</option>
<option value="individual" @selected(($filter['scope'] ?? null) === 'individual')>Физические лица</option>
</select></div>
<div class="col-md-3"><label class="form-label">Предприятие</label>
<select name="organization_id" class="form-select"><option value="">Все предприятия</option>
@foreach($organizations as $organization)
<option value="{{ $organization->id }}" @selected(($filter['organization_id'] ?? null) == $organization->id)>{{ $organization->name }}</option>
@endforeach</select></div>
<div class="col-md-2"><label class="form-label">Подгруппа</label>
<select name="group_id" class="form-select"><option value="">Все подгруппы</option>
@foreach($intake->groups as $group)
<option value="{{ $group->id }}" @selected(($filter['group_id'] ?? null) == $group->id)>{{ $group->name }}</option>
@endforeach</select></div>
<div class="col-md-3"><label class="form-label">ФИО / логин</label><input class="form-control" type="search" name="q" maxlength="120" value="{{ $filter['q'] ?? '' }}"></div>
<div class="col-md-1"><button class="btn btn-outline-primary w-100">Найти</button></div>
</form></div></div>
<p class="text-muted">Всего найдено: {{ $enrollments->total() }}. Порог изучения каждого курса: {{ $intake->required_progress_percent }}%.</p>
<div class="card shadow-sm mb-3"><div class="table-responsive">
<table class="table table-hover align-middle mb-0">
<thead><tr><th>Учащийся</th><th>Предприятие</th><th>Подгруппа</th><th>Минимальный прогресс</th><th>Обязательных экзаменов</th><th>Итог / подробности</th></tr></thead>
<tbody>@forelse($enrollments as $enrollment)
@php $state = $results[$enrollment->id]; @endphp
<tr>
<td><strong>{{ $enrollment->user?->name }}</strong><div class="small text-muted">{{ $enrollment->user?->username }}</div></td>
<td>{{ $enrollment->organization?->name ?? 'Физическое лицо' }}</td>
<td>{{ $enrollment->group?->name ?? 'Общая' }}</td>
<td>{{ collect($state['course_progress'])->min() ?? 0 }}%</td>
<td>{{ $state['passed_exam_count'] }} / {{ $state['required_exam_count'] }}</td>
<td>
<span class="{{ $state['status'] === 'passed' ? 'text-success' : 'text-secondary' }}">{{ $state['label'] }}</span>
@if($state['reasons'])<div class="small text-muted">{{ $state['reasons'] }}</div>@endif
<details class="mt-2"><summary class="small">Показать результаты по курсам и экзаменам</summary>
<ul class="small mt-2 mb-0">
@foreach($intake->courses as $item)<li>{{ $item->course?->title }}: {{ $state['course_progress'][$item->course_id] ?? 0 }}% изучено</li>@endforeach
@foreach($intake->exams->where('kind', 'official')->where('is_required', true) as $exam)
<li>{{ $exam->test?->title }}: {{ ($state['exam_results'][$exam->id] ?? false) ? 'Сдан' : 'Не сдан' }} (официальный)</li>
@endforeach
</ul></details>
</td></tr>
@empty<tr><td colspan="6" class="text-muted text-center py-4">В этом наборе нет учащихся по заданным фильтрам.</td></tr>@endforelse</tbody>
</table></div></div>
{{ $enrollments->links() }}
</main></div></div>
@endsection
