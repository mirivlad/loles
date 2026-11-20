@extends('layouts.app')
@section('title', 'Экзамен: ' . $exam->test->title)
@section('content')
<div class="container-fluid"><div class="row">
<nav class="col-md-3 col-lg-2 d-md-block sidebar"><div class="position-sticky pt-3">@include('partials._sidebar')</div></nav>
<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 pt-3 pb-2 mb-3 border-bottom"><h1 class="h2">{{ $exam->test->title }}</h1>
<a class="btn btn-outline-secondary" href="{{ route('admin.training.intakes.show', $intake) }}">К набору</a></div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<div class="card mb-4"><div class="card-body">
<dl class="row">
<dt class="col-md-3">Набор</dt><dd class="col-md-9">{{ $intake->name }}</dd>
<dt class="col-md-3">Курс</dt><dd class="col-md-9">{{ $exam->test->course?->title }}</dd>
<dt class="col-md-3">Тип</dt><dd class="col-md-9">{{ $exam->kindLabel() }}{{ $exam->is_required ? ', обязательный' : '' }}</dd>
<dt class="col-md-3">Условие сдачи</dt><dd class="col-md-9">{{ $exam->pass_mode === 'allowed_errors' ? 'Ошибок не более ' . $exam->allowed_errors : 'Верных ответов не менее ' . $exam->passing_percent . '%' }}</dd>
<dt class="col-md-3">Открыт</dt><dd class="col-md-9">{{ $exam->opens_on->format('d.m.Y') }} — {{ $exam->closes_on->format('d.m.Y') }}</dd>
<dt class="col-md-3">Лимиты</dt><dd class="col-md-9">{{ $exam->time_limit_minutes }} минут, {{ $exam->max_attempts }} попыток</dd>
<dt class="col-md-3">Доступ</dt><dd class="col-md-9">{{ $exam->is_enabled ? 'Разрешён по расписанию' : 'Отключён вручную' }}</dd>
</dl>
@if(!in_array($intake->status, ['completed', 'archived']))
<div class="d-flex flex-wrap gap-2">
@if(!$exam->attempts()->exists())<a class="btn btn-outline-primary" href="{{ route('admin.training.exams.edit', [$intake, $exam]) }}">Изменить условия</a>@endif
<form method="POST" action="{{ route('admin.training.exams.toggle', [$intake, $exam]) }}">@csrf
<input type="hidden" name="is_enabled" value="{{ $exam->is_enabled ? '0' : '1' }}">
<button class="btn {{ $exam->is_enabled ? 'btn-outline-danger' : 'btn-success' }}">{{ $exam->is_enabled ? 'Приостановить экзамен' : 'Разрешить экзамен' }}</button></form>
</div>@endif
</div></div>
<h2 class="h4">Журнал попыток</h2>
<p class="small text-muted">Аннулирование не удаляет ответы и оценку. Аннулированная попытка не влияет на результат аттестации.</p>
<div class="table-responsive"><table class="table table-hover">
<thead><tr><th>Учащийся</th><th>Попытка</th><th>Время</th><th>Правильно</th><th>Результат</th><th>Аудит</th><th></th></tr></thead><tbody>
@forelse($attempts as $attempt)<tr>
<td>{{ $attempt->user?->name }}</td><td>№{{ $attempt->attempt_number }}</td>
<td>{{ $attempt->started_at->format('d.m.Y H:i') }}</td>
<td>{{ $attempt->correct_count === null ? '—' : $attempt->correct_count . ' / ' . $attempt->total_count }}</td>
<td>@if($attempt->status === 'in_progress')В процессе
@elseif($attempt->status === 'expired')Время истекло
@else{{ $attempt->passed ? 'Сдал' : 'Не сдал' }} ({{ $attempt->score_percent }}%)@endif</td>
<td>@if($attempt->isVoided())<span class="text-danger">Аннулирована</span><div class="small">{{ $attempt->void_reason }} · {{ $attempt->voider?->name }} · {{ $attempt->voided_at->format('d.m.Y H:i') }}</div>
@else — @endif</td>
<td><a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.training.exams.attempts.show', [$intake, $exam, $attempt]) }}">Посмотреть</a>
@if(!$attempt->isVoided() && $attempt->status !== 'in_progress')
<form method="POST" class="mt-2" action="{{ route('admin.training.exams.attempts.void', [$intake, $exam, $attempt]) }}">
@csrf <label class="visually-hidden" for="reason-{{ $attempt->id }}">Причина аннулирования</label>
<input id="reason-{{ $attempt->id }}" name="reason" required minlength="10" maxlength="2000" class="form-control form-control-sm mb-1" placeholder="Причина (от 10 символов)">
<button class="btn btn-outline-danger btn-sm">Аннулировать</button></form>@endif</td></tr>
@empty<tr><td colspan="7">Попыток пока нет.</td></tr>@endforelse
</tbody></table></div>{{ $attempts->links() }}
</main></div></div>
@endsection
