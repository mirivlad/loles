@extends('layouts.app')
@section('title', 'Мои обучения')
@section('content')
<div class="container-fluid"><div class="row">
<nav class="col-md-3 col-lg-2 d-md-block sidebar"><div class="position-sticky pt-3">@include('partials._sidebar')</div></nav>
<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom"><h1 class="h2">Мои обучения</h1></div>
<p class="text-muted">Здесь отображаются все наборы, в которые вы зачислены. Материалы открываются только в сроки обучения.</p>
@forelse($enrollments as $enrollment)
@php $intake = $enrollment->intake; $canOpen = $enrollment->status === 'enrolled'
    && $intake->status === 'training' && $intake->starts_on->lte(today()) && $intake->ends_on->gte(today()); @endphp
<div class="card mb-3 shadow-sm"><div class="card-body">
<div class="d-flex flex-wrap justify-content-between gap-2">
<div><h2 class="h5">{{ $intake->name }}</h2>
<div class="text-muted">{{ $intake->program?->name }} · {{ $intake->starts_on->format('d.m.Y') }} — {{ $intake->ends_on->format('d.m.Y') }}</div>
@if($enrollment->group)<p class="small mb-0">Группа: {{ $enrollment->group->name }}</p>@endif
@if(isset($outcomes[$enrollment->id]))
<p class="small mb-0">Итог: {{ $outcomes[$enrollment->id]['label'] }}
· Обязательных экзаменов сдано: {{ $outcomes[$enrollment->id]['passed_exam_count'] }} из {{ $outcomes[$enrollment->id]['required_exam_count'] }}</p>
@endif
</div><div><span class="badge {{ $canOpen ? 'bg-success' : 'bg-secondary' }}">{{ $canOpen ? 'Обучение доступно' : ($intake->status === 'completed' ? 'Завершено' : 'Ожидает начала / закрыто') }}</span></div></div>
<hr><h3 class="h6">Курсы программы</h3><ul class="mb-0">
@foreach($intake->courses as $item)<li>
@if($item->course && $canOpen && $item->course->is_active)
<a href="{{ route('student.courses.show', $item->course) }}">{{ $item->course->title }}</a>
@else
{{ $item->course?->title ?? 'Недоступный курс' }}
@endif
@if(isset($outcomes[$enrollment->id]))
— {{ $outcomes[$enrollment->id]['course_progress'][$item->course_id] ?? 0 }}% отмечено изученным
@endif</li>@endforeach
</ul>
@if($intake->exams->isNotEmpty())
<hr><h3 class="h6">Экзамены</h3>
<ul class="mb-0">@foreach($intake->exams as $exam)
<li><a href="{{ route('student.exams.show', $exam) }}">{{ $exam->test?->title }}</a> — {{ $exam->kindLabel() }}</li>
@endforeach</ul>
@endif
</div></div>
@empty<div class="alert alert-info">Вы пока не зачислены ни на одно обучение.</div>@endforelse
{{ $enrollments->links() }}
</main></div></div>
@endsection
