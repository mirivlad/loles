@extends('layouts.app')
@section('title', 'Попытка №' . $attempt->attempt_number)
@section('content')
<div class="container-fluid"><div class="row">
<nav class="col-md-3 col-lg-2 d-md-block sidebar"><div class="position-sticky pt-3">@include('partials._sidebar')</div></nav>
<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
<h1 class="h2 pt-3 pb-2">Аудит попытки №{{ $attempt->attempt_number }}</h1>
<a class="btn btn-outline-secondary mb-3" href="{{ route('admin.training.exams.show', [$intake, $exam]) }}">Назад к экзамену</a>
<div class="card mb-4"><div class="card-body">
<p><strong>Учащийся:</strong> {{ $attempt->user?->name }} · <strong>Тип:</strong> {{ $exam->kindLabel() }}</p>
<p><strong>Начало:</strong> {{ $attempt->started_at->format('d.m.Y H:i:s') }} · <strong>Завершение:</strong> {{ $attempt->finished_at?->format('d.m.Y H:i:s') ?? '—' }}</p>
<p><strong>Результат:</strong> {{ $attempt->status === 'in_progress' ? 'В процессе' : ($attempt->passed ? 'Сдал' : 'Не сдал') }}
· {{ $attempt->correct_count ?? '—' }} / {{ $attempt->total_count ?? '—' }} ({{ $attempt->score_percent ?? '—' }}%)</p>
@if($attempt->isVoided())<div class="alert alert-danger">Аннулировано: {{ $attempt->void_reason }}. Сотрудник: {{ $attempt->voider?->name }}</div>@endif
</div></div>
@foreach($attempt->questions as $index => $question)
<div class="card mb-3"><div class="card-body"><h2 class="h6">Вопрос {{ $index + 1 }}: {{ $question->question_text }}</h2>
<p class="small">Результат: {{ $question->is_correct === null ? 'Ответ не принят' : ($question->is_correct ? 'Верно' : 'Ошибка') }}</p>
<ul>@foreach($question->options_snapshot as $option)
<li>
@if(in_array($option['id'], $question->selected_ids ?? []))<strong>[Выбрано]</strong>@endif
{{ $option['text'] ?: 'Ответ с изображением' }}
@if($option['correct'])<span class="text-success">(верный вариант)</span>@endif
</li>@endforeach</ul></div></div>
@endforeach
</main></div></div>
@endsection
