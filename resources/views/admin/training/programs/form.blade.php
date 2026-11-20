@extends('layouts.app')
@section('title', $program->exists ? 'Изменить программу' : 'Новая программа')
@section('content')
<div class="container-fluid"><div class="row">
<nav class="col-md-3 col-lg-2 d-md-block sidebar"><div class="position-sticky pt-3">@include('partials._sidebar')</div></nav>
<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
<h1 class="h2 pt-3 pb-3">{{ $program->exists ? 'Изменить программу' : 'Новая программа' }}</h1>
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form method="POST" action="{{ $program->exists ? route('admin.training.programs.update', $program) : route('admin.training.programs.store') }}" class="card shadow-sm">
@csrf @if($program->exists) @method('PUT') @endif
<div class="card-body">
<div class="mb-3"><label class="form-label">Название программы</label><input name="name" class="form-control" required maxlength="255" value="{{ old('name', $program->name) }}"></div>
<div class="mb-3"><label class="form-label">Описание</label><textarea name="description" class="form-control" rows="4">{{ old('description', $program->description) }}</textarea></div>
<div class="mb-3"><label class="form-label">Состояние</label><select name="is_active" class="form-select"><option value="1" @selected(old('is_active', (int) $program->is_active) == 1)>Действующая</option><option value="0" @selected(old('is_active', (int) $program->is_active) == 0)>Отключена</option></select></div>
<button class="btn btn-primary">Сохранить</button><a class="btn btn-outline-secondary" href="{{ route('admin.training.programs.index') }}">Отмена</a>
</div></form></main></div></div>
@endsection
