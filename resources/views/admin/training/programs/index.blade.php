@extends('layouts.app')
@section('title', 'Программы обучения')
@section('content')
<div class="container-fluid"><div class="row">
<nav class="col-md-3 col-lg-2 d-md-block sidebar"><div class="position-sticky pt-3">@include('partials._sidebar')</div></nav>
<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
<h1 class="h2">Программы обучения</h1><a class="btn btn-primary" href="{{ route('admin.training.programs.create') }}">Создать программу</a></div>
<form class="row g-2 mb-3" method="GET"><div class="col-lg-6"><input class="form-control" name="q" value="{{ request('q') }}" placeholder="Поиск по названию"></div><div class="col-auto"><button class="btn btn-outline-primary">Найти</button></div></form>
<div class="table-responsive"><table class="table table-hover"><thead><tr><th>Программа</th><th>Курсов</th><th>Наборов</th><th>Состояние</th></tr></thead>
<tbody>@forelse($programs as $program)<tr><td><a href="{{ route('admin.training.programs.show', $program) }}">{{ $program->name }}</a></td><td>{{ $program->courses_count }}</td><td>{{ $program->intakes_count }}</td><td>{{ $program->is_active ? 'Действующая' : 'Отключена' }}</td></tr>
@empty<tr><td colspan="4">Программ пока нет.</td></tr>@endforelse</tbody></table></div>
{{ $programs->links() }}</main></div></div>
@endsection
