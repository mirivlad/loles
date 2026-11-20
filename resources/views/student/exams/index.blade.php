@extends('layouts.app')
@section('title', 'Экзамены по моим обучениям')
@section('content')
<div class="container-fluid"><div class="row">
<nav class="col-md-3 col-lg-2 d-md-block sidebar"><div class="position-sticky pt-3">@include('partials._sidebar')</div></nav>
<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom"><h1 class="h2">Экзамены по моим обучениям</h1></div>
<p class="text-muted">Пробные экзамены не входят в официальную аттестацию. Доступ определяется расписанием и решением учебного центра.</p>
<div class="table-responsive"><table class="table table-hover">
<thead><tr><th>Экзамен</th><th>Набор</th><th>Тип</th><th>Срок</th><th>Попытки</th><th>Доступ</th></tr></thead>
<tbody>@forelse($exams as $exam)<tr>
<td><a href="{{ route('student.exams.show', $exam) }}">{{ $exam->test?->title }}</a><div class="small text-muted">{{ $exam->test?->course?->title }}</div></td>
<td>{{ $exam->intake?->name }}</td><td>{{ $exam->kindLabel() }}</td>
<td>{{ $exam->opens_on->format('d.m.Y') }} — {{ $exam->closes_on->format('d.m.Y') }}</td>
<td>{{ $attemptCounts[$exam->id] ?? 0 }} / {{ $exam->max_attempts }}</td>
<td>{{ $exam->acceptsAttempts() ? 'Открыт' : 'Закрыт' }}</td>
</tr>@empty<tr><td colspan="6">Для ваших обучений экзамены пока не назначены.</td></tr>@endforelse</tbody></table></div>
</main></div></div>
@endsection
