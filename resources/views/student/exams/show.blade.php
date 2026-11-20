@extends('layouts.app')
@section('title', $exam->test->title)
@section('content')
<div class="container-fluid"><div class="row">
<nav class="col-md-3 col-lg-2 d-md-block sidebar"><div class="position-sticky pt-3">@include('partials._sidebar')</div></nav>
<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
<div class="d-flex flex-wrap justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
<h1 class="h2">{{ $exam->test->title }}</h1><a href="{{ route('student.exams.index') }}" class="btn btn-outline-secondary">Все экзамены</a></div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<div class="card mb-3"><div class="card-body">
<p><strong>Набор:</strong> {{ $exam->intake->name }} · <strong>Вид:</strong> {{ $exam->kindLabel() }}</p>
<p><strong>Время:</strong> {{ $exam->time_limit_minutes }} мин. · <strong>Всего попыток:</strong> {{ $exam->max_attempts }}</p>
<p><strong>Условие сдачи:</strong> {{ $exam->pass_mode === 'allowed_errors' ? 'Не более ' . $exam->allowed_errors . ' ошибок' : 'Не менее ' . $exam->passing_percent . '% правильных ответов' }}</p>
<p><strong>Доступен с</strong> {{ $exam->opens_on->format('d.m.Y') }} <strong>по</strong> {{ $exam->closes_on->format('d.m.Y') }} включительно.</p>
<p class="small text-muted">По нажатию кнопки начинается отсчёт времени. После окончания времени новые ответы не принимаются.</p>
@php $used = $attempts->filter(fn ($a) => !$a->isVoided())->count(); @endphp
@if($exam->acceptsAttempts() && $used < $exam->max_attempts)
<form method="POST" action="{{ route('student.exams.start', $exam) }}">@csrf
<button class="btn btn-primary">Начать / продолжить попытку</button></form>
@else<div class="alert alert-secondary mb-0">Новая попытка недоступна: экзамен закрыт или лимит исчерпан.</div>@endif
</div></div>
<h2 class="h4">История моих попыток</h2>
<div class="table-responsive"><table class="table"><thead><tr><th>Попытка</th><th>Дата</th><th>Состояние</th><th>Результат</th><th></th></tr></thead><tbody>
@forelse($attempts as $attempt)<tr>
<td>№{{ $attempt->attempt_number }}</td><td>{{ $attempt->started_at->format('d.m.Y H:i') }}</td>
<td>{{ $attempt->isVoided() ? 'Аннулирована' : match($attempt->status) {'in_progress'=>'Проходит','expired'=>'Время истекло',default=>'Завершена'} }}</td>
<td>{{ $attempt->score_percent === null ? '—' : $attempt->score_percent . '% (' . ($attempt->passed ? 'сдан' : 'не сдан') . ')' }}</td>
<td><a href="{{ route('student.exams.attempt.show', [$exam, $attempt]) }}" class="btn btn-sm btn-outline-primary">Посмотреть</a></td>
</tr>@empty<tr><td colspan="5">Пока нет попыток.</td></tr>@endforelse</tbody></table></div>
</main></div></div>
@endsection
