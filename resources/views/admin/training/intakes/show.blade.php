@extends('layouts.app')
@section('title', $intake->name)
@section('content')
<div class="container-fluid"><div class="row">
<nav class="col-md-3 col-lg-2 d-md-block sidebar"><div class="position-sticky pt-3">@include('partials._sidebar')</div></nav>
<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
<h1 class="h2">{{ $intake->name }}</h1><div>
@if(!in_array($intake->status, ['completed','archived']))
<a href="{{ route('admin.training.intakes.edit', $intake) }}" class="btn btn-outline-secondary">Настроить</a>
@elseif($intake->status === 'completed')
<form method="POST" action="{{ route('admin.training.intakes.archive', $intake) }}" class="d-inline">@csrf
<button class="btn btn-outline-secondary">В архив</button></form>
@endif
@if($intake->acceptsApplications())<a href="{{ route('admin.training.applications.create', $intake) }}" class="btn btn-primary">Новая заявка</a>@endif</div></div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<div class="row g-3 mb-4">
<div class="col-lg-6"><div class="card h-100"><div class="card-body">
<h2 class="h5">Параметры</h2><dl class="row mb-0">
<dt class="col-sm-5">Программа</dt><dd class="col-sm-7"><a href="{{ route('admin.training.programs.show', $intake->program) }}">{{ $intake->program->name }}</a></dd>
<dt class="col-sm-5">Приём заявок</dt><dd class="col-sm-7">до {{ $intake->application_deadline->format('d.m.Y') }} включительно</dd>
<dt class="col-sm-5">Обучение</dt><dd class="col-sm-7">{{ $intake->starts_on->format('d.m.Y') }} — {{ $intake->ends_on->format('d.m.Y') }}</dd>
<dt class="col-sm-5">Зачислено</dt><dd class="col-sm-7">{{ $intake->enrollments_count }} / {{ $intake->capacity }}</dd>
<dt class="col-sm-5">Порог освоения</dt><dd class="col-sm-7">{{ $intake->required_progress_percent }}%</dd>
<dt class="col-sm-5">Статус</dt><dd class="col-sm-7">{{ $intake->statusLabel() }}</dd>
</dl><p class="text-muted mb-0">{{ $intake->notes }}</p></div></div></div>
<div class="col-lg-6"><div class="card h-100"><div class="card-body">
<h2 class="h5">Курсы набора</h2><ul>@foreach($intake->courses as $item)<li>{{ $item->course?->title ?? 'Недоступный курс' }}</li>@endforeach</ul>
<p class="small text-muted">Состав зафиксирован при создании набора.</p>
<h2 class="h5">Учебные группы</h2><ul>@forelse($intake->groups as $group)<li>{{ $group->name }} — {{ $group->enrollments()->count() }} чел.</li>@empty<li>Нет подгрупп. Все могут обучаться вместе.</li>@endforelse</ul>
@if(!in_array($intake->status, ['completed','archived']))
<form method="POST" action="{{ route('admin.training.intakes.groups.add', $intake) }}" class="row g-2">@csrf
<div class="col-8"><input class="form-control" name="name" required maxlength="255" placeholder="Название подгруппы"></div>
<div class="col-4"><button class="btn btn-outline-primary">Добавить</button></div></form>@endif
</div></div></div></div>
<h2 class="h4">Заявки ({{ $intake->applications_count }})</h2>
<div class="table-responsive"><table class="table table-hover"><thead><tr><th>Заявитель</th><th>Учащихся</th><th>Решение</th><th>Оплата</th></tr></thead><tbody>
@forelse($applications as $application)<tr><td><a href="{{ route('admin.training.applications.show', [$intake, $application]) }}">{{ $application->organization?->name ?? $application->contact_name }}</a></td><td>{{ $application->students->count() }}</td><td>{{ ['pending'=>'На рассмотрении','approved'=>'Одобрена','rejected'=>'Отклонена'][$application->status] ?? $application->status }}</td><td>{{ ['unpaid'=>'Не оплачена','paid'=>'Оплачена','exempt'=>'Без оплаты'][$application->payment_status] ?? $application->payment_status }}</td></tr>
@empty<tr><td colspan="4">Заявок пока нет.</td></tr>@endforelse</tbody></table></div>{{ $applications->links() }}
<h2 class="h4 mt-4">Зачисленные учащиеся</h2>
<div class="table-responsive"><table class="table table-hover"><thead><tr><th>ФИО</th><th>Логин</th><th>Предприятие</th><th>Подгруппа</th><th>Состояние</th></tr></thead><tbody>
@forelse($enrollments as $enrollment)<tr><td>{{ $enrollment->user?->name }}</td><td>{{ $enrollment->user?->username }}</td>
<td>{{ $enrollment->organization?->name ?? 'Физлицо' }}</td>
<td>@if(in_array($intake->status, ['completed','archived']))
{{ $enrollment->group?->name ?? 'Общая' }}
@else
<form method="POST" action="{{ route('admin.training.intakes.enrollments.group', [$intake, $enrollment]) }}" class="d-flex gap-1">
@csrf @method('PUT')
<select name="training_intake_group_id" class="form-select form-select-sm" aria-label="Подгруппа">
<option value="">Общая</option>
@foreach($intake->groups as $group)<option value="{{ $group->id }}" @selected($enrollment->training_intake_group_id === $group->id)>{{ $group->name }}</option>@endforeach
</select><button class="btn btn-sm btn-outline-primary">ОК</button>
</form>@endif</td><td>{{ $enrollment->status === 'enrolled' ? 'Зачислен' : $enrollment->status }}</td></tr>
@empty<tr><td colspan="5">Зачислений пока нет.</td></tr>@endforelse</tbody></table></div>{{ $enrollments->links() }}
</main></div></div>
@endsection
