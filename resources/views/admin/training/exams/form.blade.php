@extends('layouts.app')
@section('title', $exam->exists ? 'Правило экзамена' : 'Добавить экзамен')
@section('content')
<div class="container-fluid"><div class="row">
<nav class="col-md-3 col-lg-2 d-md-block sidebar"><div class="position-sticky pt-3">@include('partials._sidebar')</div></nav>
<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom"><h1 class="h2">{{ $exam->exists ? 'Правило экзамена' : 'Добавить экзамен' }}</h1>
<a class="btn btn-outline-secondary" href="{{ route('admin.training.intakes.show', $intake) }}">Назад к набору</a></div>
<p class="text-muted">Набор: {{ $intake->name }}. Поддерживаются вопросы с выбором одного или нескольких вариантов.</p>
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form class="card shadow-sm" method="POST" action="{{ $exam->exists ? route('admin.training.exams.update', [$intake, $exam]) : route('admin.training.exams.store', $intake) }}">
@csrf @if($exam->exists) @method('PUT') @endif
<div class="card-body">
<div class="row g-3 mb-3">
<div class="col-lg-6"><label class="form-label" for="exam-test">Тест курса</label><select id="exam-test" class="form-select" name="test_id" required>
<option value="">Выберите тест</option>
@foreach($tests as $test)<option value="{{ $test->id }}" @selected(old('test_id', $exam->test_id) == $test->id)>{{ $test->course?->title }} — {{ $test->title }}</option>@endforeach
</select></div>
<div class="col-lg-3"><label class="form-label">Вид экзамена</label><select name="kind" class="form-select" required>
<option value="official" @selected(old('kind', $exam->kind) === 'official')>Официальный</option>
<option value="trial" @selected(old('kind', $exam->kind) === 'trial')>Пробный</option>
</select></div>
<div class="col-lg-3"><label class="form-label">Обязательность</label><select name="is_required" class="form-select">
<option value="1" @selected(old('is_required', (int) $exam->is_required) == 1)>Обязательный</option>
<option value="0" @selected(old('is_required', (int) $exam->is_required) == 0)>Необязательный</option>
</select><div class="form-text">Пробные никогда не учитываются как обязательные.</div></div>
</div>
<div class="row g-3 mb-3">
<div class="col-lg-4"><label class="form-label">Условие сдачи</label><select name="pass_mode" class="form-select" id="pass-mode">
<option value="allowed_errors" @selected(old('pass_mode', $exam->pass_mode) === 'allowed_errors')>Максимальное число ошибок</option>
<option value="percent" @selected(old('pass_mode', $exam->pass_mode) === 'percent')>Не менее N% правильных ответов</option></select></div>
<div class="col-lg-4" id="allowed-errors-field"><label class="form-label">Допустимое число ошибок</label><input name="allowed_errors" type="number" min="0" class="form-control" value="{{ old('allowed_errors', $exam->allowed_errors ?? 0) }}"></div>
<div class="col-lg-4" id="passing-percent-field"><label class="form-label">Процент правильных ответов</label><input name="passing_percent" type="number" min="0" max="100" class="form-control" value="{{ old('passing_percent', $exam->passing_percent ?? 80) }}"></div>
</div>
<div class="row g-3 mb-3">
<div class="col-lg-3"><label class="form-label">Открыт с (включительно)</label><input type="date" name="opens_on" class="form-control" required value="{{ old('opens_on', $exam->opens_on?->toDateString()) }}"></div>
<div class="col-lg-3"><label class="form-label">По (включительно)</label><input type="date" name="closes_on" class="form-control" required value="{{ old('closes_on', $exam->closes_on?->toDateString()) }}"></div>
<div class="col-lg-3"><label class="form-label">Минут на попытку</label><input name="time_limit_minutes" type="number" min="1" max="480" class="form-control" required value="{{ old('time_limit_minutes', $exam->time_limit_minutes) }}"></div>
<div class="col-lg-3"><label class="form-label">Максимум попыток</label><input name="max_attempts" type="number" min="1" max="100" class="form-control" required value="{{ old('max_attempts', $exam->max_attempts) }}"></div>
</div>
<p class="small text-muted">После первой попытки условия нельзя менять. Доступ включается отдельно со страницы экзамена; до этого даже в пределах дат новые попытки недоступны.</p>
<button class="btn btn-primary">Сохранить правило</button>
<a class="btn btn-outline-secondary" href="{{ route('admin.training.intakes.show', $intake) }}">Отмена</a>
</div></form>
</main></div></div>
@push('scripts')
<script>
(function(){
 const s=document.getElementById('pass-mode');
 function sync(){
   document.getElementById('allowed-errors-field').hidden=s.value!=='allowed_errors';
   document.getElementById('passing-percent-field').hidden=s.value!=='percent';
 }
 s.addEventListener('change',sync);sync();
})();
</script>
@endpush
@endsection
