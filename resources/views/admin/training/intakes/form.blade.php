@extends('layouts.app')
@section('title', $intake->exists ? 'Изменить набор' : 'Новый набор')
@section('content')
<div class="container-fluid"><div class="row">
<nav class="col-md-3 col-lg-2 d-md-block sidebar"><div class="position-sticky pt-3">@include('partials._sidebar')</div></nav>
<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
<h1 class="h2 pt-3 pb-3">{{ $intake->exists ? 'Изменить набор' : 'Новый набор' }}</h1>
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form class="card shadow-sm" method="POST" action="{{ $intake->exists ? route('admin.training.intakes.update', $intake) : route('admin.training.intakes.store') }}">
@csrf @if($intake->exists) @method('PUT') @endif
<div class="card-body">
<div class="mb-3"><label class="form-label">Название набора</label><input class="form-control" name="name" required maxlength="255" value="{{ old('name', $intake->name) }}" placeholder="Сварщики, декабрь 2026"></div>
<div class="mb-3"><label class="form-label">Программа обучения</label>
<select class="form-select" name="training_program_id" @disabled($intake->exists) required>
<option value="">Выберите программу</option>@foreach($programs as $program)
<option value="{{ $program->id }}" @selected(old('training_program_id', $intake->training_program_id ?: request('program_id')) == $program->id)>{{ $program->name }}</option>
@endforeach</select>
@if($intake->exists)<div class="form-text">Программа фиксируется при создании набора.</div>@endif</div>
<div class="row g-3 mb-3">
<div class="col-md-4"><label class="form-label">Приём заявок до (включительно)</label><input class="form-control" type="date" name="application_deadline" required value="{{ old('application_deadline', $intake->application_deadline?->toDateString()) }}"></div>
<div class="col-md-4"><label class="form-label">Начало обучения</label><input class="form-control" type="date" name="starts_on" required value="{{ old('starts_on', $intake->starts_on?->toDateString()) }}"></div>
<div class="col-md-4"><label class="form-label">Окончание обучения</label><input class="form-control" type="date" name="ends_on" required value="{{ old('ends_on', $intake->ends_on?->toDateString()) }}"></div></div>
<div class="row g-3 mb-3">
<div class="col-md-4"><label class="form-label">Количество мест</label><input class="form-control" type="number" min="1" name="capacity" required value="{{ old('capacity', $intake->capacity ?: 50) }}"></div>
<div class="col-md-4"><label class="form-label">Минимальный процент освоения</label><input class="form-control" type="number" min="0" max="100" name="required_progress_percent" required value="{{ old('required_progress_percent', $intake->required_progress_percent ?? 100) }}"><div class="form-text">Будет использован при расчёте итоговой аттестации.</div></div>
<div class="col-md-4"><label class="form-label">Состояние</label><select class="form-select" name="status">
@foreach(['draft'=>'Черновик','open'=>'Приём заявок','closed'=>'Приём завершён','training'=>'Обучение','completed'=>'Завершён','archived'=>'Архив'] as $code=>$label)
<option value="{{ $code }}" @selected(old('status', $intake->status) === $code)>{{ $label }}</option>@endforeach</select></div>
</div>
<div class="mb-3"><label class="form-label">Примечание менеджера</label><textarea class="form-control" name="notes" rows="3">{{ old('notes', $intake->notes) }}</textarea></div>
<button class="btn btn-primary">Сохранить</button><a class="btn btn-outline-secondary" href="{{ route('admin.training.intakes.index') }}">Отмена</a>
</div></form>
</main></div></div>
@endsection
