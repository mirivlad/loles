@extends('layouts.app')
@section('title', 'Наборы на обучение')
@section('content')
<div class="container-fluid"><div class="row">
<nav class="col-md-3 col-lg-2 d-md-block sidebar"><div class="position-sticky pt-3">@include('partials._sidebar')</div></nav>
<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
<h1 class="h2">Наборы на обучение</h1><a class="btn btn-primary" href="{{ route('admin.training.intakes.create') }}">Новый набор</a></div>
<form method="GET" class="row g-2 mb-3">
<div class="col-md-5"><input class="form-control" name="q" value="{{ request('q') }}" placeholder="Название набора"></div>
<div class="col-md-4"><select name="status" class="form-select"><option value="">Все статусы</option>
@foreach(['draft'=>'Черновик','open'=>'Приём заявок','closed'=>'Приём завершён','training'=>'Обучение','completed'=>'Завершён','archived'=>'Архив'] as $code=>$label)
<option value="{{ $code }}" @selected(request('status') === $code)>{{ $label }}</option>@endforeach
</select></div><div class="col-auto"><button class="btn btn-outline-primary">Найти</button></div></form>
<div class="table-responsive"><table class="table table-hover">
<thead><tr><th>Набор</th><th>Программа</th><th>Приём до</th><th>Обучение</th><th>Зачислено</th><th>Заявок</th><th>Состояние</th></tr></thead>
<tbody>@forelse($intakes as $intake)<tr>
<td><a href="{{ route('admin.training.intakes.show', $intake) }}">{{ $intake->name }}</a></td>
<td>{{ $intake->program?->name }}</td><td>{{ $intake->application_deadline->format('d.m.Y') }}</td>
<td>{{ $intake->starts_on->format('d.m.Y') }} — {{ $intake->ends_on->format('d.m.Y') }}</td>
<td>{{ $intake->enrollments_count }} / {{ $intake->capacity }}</td><td>{{ $intake->applications_count }}</td>
<td>{{ $intake->statusLabel() }}</td></tr>
@empty<tr><td colspan="7">Наборов пока нет.</td></tr>@endforelse</tbody></table></div>
{{ $intakes->links() }}</main></div></div>
@endsection
