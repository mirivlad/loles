@extends('layouts.app')
@section('title', 'Ведомости моего предприятия')
@section('content')
<div class="container-fluid"><div class="row">
<nav class="col-md-3 col-lg-2 d-md-block sidebar"><div class="position-sticky pt-3">@include('partials._sidebar')</div></nav>
<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
<div class="pt-3 pb-2 mb-3 border-bottom">
<h1 class="h2">Ведомости моего предприятия</h1>
<p class="text-muted">Показаны только те наборы, в которых есть учащиеся вашего предприятия. Чужие предприятия и физические лица недоступны.</p>
</div>
<div class="table-responsive"><table class="table table-hover">
<thead><tr><th>Набор</th><th>Программа</th><th>Период</th><th>Учащихся предприятия</th><th></th></tr></thead><tbody>
@forelse($intakes as $intake)
<tr><td>{{ $intake->name }}</td><td>{{ $intake->program?->name }}</td>
<td>{{ $intake->starts_on->format('d.m.Y') }} — {{ $intake->ends_on->format('d.m.Y') }}</td>
<td>{{ $intake->enrollments_count }}</td>
<td><a href="{{ route('admin.training.reports.show', $intake) }}" class="btn btn-sm btn-outline-primary">Открыть ведомость</a></td></tr>
@empty<tr><td colspan="5" class="text-muted py-4">Для вашего предприятия пока нет зачислений.</td></tr>
@endforelse</tbody></table></div>
{{ $intakes->links() }}
</main></div></div>
@endsection
