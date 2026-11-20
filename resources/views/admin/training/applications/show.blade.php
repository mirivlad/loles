@extends('layouts.app')
@section('title', 'Заявка №' . $application->id)
@section('content')
<div class="container-fluid"><div class="row">
<nav class="col-md-3 col-lg-2 d-md-block sidebar"><div class="position-sticky pt-3">@include('partials._sidebar')</div></nav>
<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
<div class="d-flex flex-wrap justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
<h1 class="h2">Заявка №{{ $application->id }}</h1>
<a href="{{ route('admin.training.intakes.show', $intake) }}" class="btn btn-outline-secondary">Назад к набору</a></div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="card mb-3"><div class="card-body">
<dl class="row mb-0">
<dt class="col-sm-3">Набор</dt><dd class="col-sm-9">{{ $intake->name }}</dd>
<dt class="col-sm-3">Заявитель</dt><dd class="col-sm-9">{{ $application->organization?->name ?? 'Физическое лицо' }}</dd>
<dt class="col-sm-3">Контакт</dt><dd class="col-sm-9">{{ $application->contact_name }} · {{ $application->contact_phone }} · {{ $application->contact_email }}</dd>
<dt class="col-sm-3">Рассмотрение</dt><dd class="col-sm-9">{{ ['pending'=>'Ожидает','approved'=>'Одобрена','rejected'=>'Отклонена'][$application->status] ?? $application->status }}</dd>
<dt class="col-sm-3">Оплата</dt><dd class="col-sm-9">{{ ['unpaid'=>'Не оплачена','paid'=>'Оплачена','exempt'=>'Не требуется'][$application->payment_status] ?? $application->payment_status }}</dd>
<dt class="col-sm-3">Комментарий</dt><dd class="col-sm-9">{{ $application->notes ?: '—' }}</dd>
</dl></div></div>
<h2 class="h4">Список учащихся</h2>
<div class="table-responsive"><table class="table"><thead><tr><th>ФИО</th><th>Логин</th><th>Зачисление</th></tr></thead><tbody>
@foreach($application->students as $student)<tr><td>{{ $student->full_name }}</td><td>{{ $student->user?->username ?? 'Будет создан' }}</td>
<td>{{ $student->enrollment ? 'Зачислен' : 'Ожидает зачисления' }}</td></tr>@endforeach
</tbody></table></div>
<div class="row g-3">
<div class="col-lg-6"><div class="card"><div class="card-header">Рассмотрение заявки</div><div class="card-body">
@if($application->status === 'pending')
<div class="d-flex gap-2"><form method="POST" action="{{ route('admin.training.applications.review', [$intake, $application]) }}">@csrf
<input type="hidden" name="decision" value="approved"><button class="btn btn-success">Одобрить</button></form>
<form method="POST" action="{{ route('admin.training.applications.review', [$intake, $application]) }}" onsubmit="return confirm('Отклонить заявку?');">@csrf
<input type="hidden" name="decision" value="rejected"><button class="btn btn-outline-danger">Отклонить</button></form></div>
@else<p class="mb-0">Решение сохранено; повторное рассмотрение не предусмотрено.</p>@endif
</div></div></div>
<div class="col-lg-6"><div class="card"><div class="card-header">Оплата</div><div class="card-body">
<form method="POST" action="{{ route('admin.training.applications.payment', [$intake, $application]) }}" class="d-flex flex-wrap gap-2 align-items-center">@csrf
<select name="payment_status" class="form-select w-auto">
<option value="unpaid" @selected($application->payment_status === 'unpaid')>Не оплачена</option>
<option value="paid" @selected($application->payment_status === 'paid')>Оплачена</option>
<option value="exempt" @selected($application->payment_status === 'exempt')>Без оплаты</option>
</select><button class="btn btn-outline-primary">Сохранить</button>
</form></div></div></div>
</div>
<div class="card mt-3 mb-4"><div class="card-header"><strong>Зачисление</strong></div><div class="card-body">
<p class="text-muted small">Зачисление доступно только после одобрения и подтверждения оплаты. Все ожидающие учащиеся обрабатываются атомарно — если мест не хватает, список сохраняется.</p>
<form method="POST" action="{{ route('admin.training.applications.enroll', [$intake, $application]) }}" class="row g-2 align-items-end">@csrf
<div class="col-md-6"><label class="form-label">Учебная группа набора</label>
<select name="training_intake_group_id" class="form-select"><option value="">Без подгруппы</option>
@foreach($intake->groups as $group)<option value="{{ $group->id }}">{{ $group->name }}</option>@endforeach
</select></div><div class="col-md-6"><button class="btn btn-primary" @disabled($application->status !== 'approved' || !in_array($application->payment_status, ['paid','exempt']))>Зачислить учащихся</button></div></form>
</div></div>
</main></div></div>
@endsection
