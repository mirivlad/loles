@extends('layouts.app')
@section('title', 'Попытка №' . $attempt->attempt_number)
@section('content')
<div class="container-fluid"><div class="row">
<nav class="col-md-3 col-lg-2 d-md-block sidebar"><div class="position-sticky pt-3">@include('partials._sidebar')</div></nav>
<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
<div class="d-flex flex-wrap justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
<h1 class="h2">{{ $exam->test?->title }} — попытка №{{ $attempt->attempt_number }}</h1>
<a class="btn btn-outline-secondary" href="{{ route('student.exams.show', $exam) }}">К экзамену</a></div>
@if(session('success'))<div class="alert alert-info">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
@if($attempt->status === 'in_progress' && !$attempt->isVoided())
<div class="alert alert-warning d-flex justify-content-between">
<span>Время ограничено. Все ответы отправляются одной формой.</span>
<span><strong id="exam-timer" data-expires="{{ $attempt->expires_at->toIso8601String() }}">—</strong></span>
</div>
<form method="POST" action="{{ route('student.exams.attempt.submit', [$exam, $attempt]) }}">@csrf
@foreach($attempt->questions as $index => $question)
<div class="card shadow-sm mb-3"><div class="card-body">
<h2 class="h5">Вопрос {{ $index + 1 }} из {{ $attempt->questions->count() }}</h2>
<p>{{ $question->question_text }}</p>
<fieldset><legend class="fs-6 text-muted">Выберите все правильные варианты</legend>
@foreach($question->options_snapshot as $option)
<label class="d-flex align-items-start gap-2 mb-2">
<input type="checkbox" class="form-check-input" name="answers[{{ $question->id }}][]" value="{{ $option['id'] }}">
<span>{{ $option['text'] ?: 'Вариант с изображением' }}
@if(!empty($option['image']))<img src="{{ asset('storage/' . $option['image']) }}" alt="Изображение ответа" class="img-fluid d-block mt-1" style="max-width: 320px;">@endif</span>
</label>
@endforeach
</fieldset>
</div></div>
@endforeach
<p class="text-muted small">Пустой ответ считается ошибкой. Отправка завершает попытку без возможности исправления.</p>
<button class="btn btn-primary btn-lg mb-4">Завершить попытку и проверить ответы</button>
</form>
@push('scripts')
<script>
(function () {
 const label=document.getElementById('exam-timer');
 if(!label) return;
 const end=Date.parse(label.dataset.expires);
 function tick(){
   const seconds=Math.max(0,Math.ceil((end-Date.now())/1000));
   label.textContent=Math.floor(seconds/60)+':'+String(seconds%60).padStart(2,'0');
   if(seconds===0) { label.closest('.alert').classList.add('alert-danger'); }
 }
 tick();setInterval(tick,1000);
})();
</script>
@endpush
@else
<div class="card mb-4"><div class="card-body">
@if($attempt->isVoided())<div class="alert alert-danger">Попытка аннулирована. Результат не засчитывается.</div>@endif
@if($attempt->status === 'expired')<h2 class="h5">Время попытки истекло</h2>
<p>Ответы не были приняты в установленный срок.</p>
@else<h2 class="h5">Результат: {{ $attempt->passed ? 'Сдан' : 'Не сдан' }}</h2>
<p>Верно: {{ $attempt->correct_count }} из {{ $attempt->total_count }}. Ошибок: {{ $attempt->error_count }}. Результат: {{ $attempt->score_percent }}%.</p>
@endif
<p class="small text-muted">Правильные варианты скрыты. История ваших результатов сохраняется.</p>
</div></div>
@endif
</main></div></div>
@endsection
