@extends('layouts.app')
@section('title', $program->name)
@section('content')
<div class="container-fluid"><div class="row">
<nav class="col-md-3 col-lg-2 d-md-block sidebar"><div class="position-sticky pt-3">@include('partials._sidebar')</div></nav>
<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
<div class="d-flex flex-wrap justify-content-between gap-2 align-items-center pt-3 pb-2 mb-3 border-bottom">
<h1 class="h2">{{ $program->name }}</h1><div>
<a class="btn btn-outline-secondary" href="{{ route('admin.training.programs.edit', $program) }}">Изменить</a>
<a class="btn btn-primary" href="{{ route('admin.training.intakes.create', ['program_id' => $program->id]) }}">Создать набор</a></div></div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<p>{{ $program->description }}</p>
<div class="card mb-4"><div class="card-header"><strong>Курсы программы</strong></div><div class="card-body">
<p class="text-muted small">Состав уже созданных наборов не меняется при редактировании программы.</p>
<div class="table-responsive"><table class="table"><thead><tr><th>Курс</th><th></th></tr></thead><tbody>
@forelse($program->courses as $item)<tr><td>{{ $item->course?->title ?? 'Недоступный курс' }}</td>
<td class="text-end"><form method="POST" action="{{ route('admin.training.programs.courses.remove', [$program, $item->course_id]) }}" onsubmit="return confirm('Убрать курс из программы?');">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Удалить</button></form></td></tr>
@empty<tr><td colspan="2">Пока нет курсов. Добавьте хотя бы один для создания набора.</td></tr>@endforelse</tbody></table></div>
<form method="POST" action="{{ route('admin.training.programs.courses.add', $program) }}" class="row g-2 align-items-end">@csrf
<div class="col-md-8"><label class="form-label">Добавить курс</label>
<x-searchable-select name="course_id" url="{{ route('api.courses.search') }}" placeholder="Начните вводить название курса..." :required="true" /></div>
<div class="col-md-4"><button class="btn btn-primary">Добавить</button></div></form>
</div></div>
<div class="card"><div class="card-header"><strong>Наборы по программе</strong></div><div class="table-responsive"><table class="table mb-0">
<thead><tr><th>Набор</th><th>Период</th><th>Статус</th></tr></thead><tbody>
@forelse($intakes as $intake)<tr><td><a href="{{ route('admin.training.intakes.show', $intake) }}">{{ $intake->name }}</a></td>
<td>{{ $intake->starts_on->format('d.m.Y') }} — {{ $intake->ends_on->format('d.m.Y') }}</td><td>{{ $intake->statusLabel() }}</td></tr>
@empty<tr><td colspan="3">Наборов ещё нет.</td></tr>@endforelse</tbody></table></div></div>{{ $intakes->links() }}
</main></div></div>
@endsection
