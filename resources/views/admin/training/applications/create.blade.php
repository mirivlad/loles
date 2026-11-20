@extends('layouts.app')
@section('title', 'Новая заявка')
@section('content')
<div class="container-fluid"><div class="row">
<nav class="col-md-3 col-lg-2 d-md-block sidebar"><div class="position-sticky pt-3">@include('partials._sidebar')</div></nav>
<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
<h1 class="h2 pt-3 pb-3">Новая заявка: {{ $intake->name }}</h1>
<p class="text-muted">Приём заявок до {{ $intake->application_deadline->format('d.m.Y') }} включительно. Подача заявки не открывает доступ к курсам.</p>
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form class="card shadow-sm" method="POST" action="{{ route('admin.training.applications.store', $intake) }}">@csrf
<div class="card-body">
<div class="mb-3"><label class="form-label">Заявитель</label>
<select name="applicant_type" id="applicant-type" class="form-select" required>
<option value="organization" @selected(old('applicant_type') === 'organization')>Предприятие</option>
<option value="individual" @selected(old('applicant_type') === 'individual')>Физическое лицо</option></select></div>
<div id="enterprise-field" class="mb-3"><label class="form-label">Предприятие</label>
<x-searchable-select name="organization_id" url="{{ route('api.organizations.search') }}" placeholder="Найдите предприятие..." :value="old('organization_id')" /></div>
<div class="row g-3 mb-3"><div class="col-md-4"><label class="form-label">Контактное лицо / ФИО физлица</label><input name="contact_name" class="form-control" required value="{{ old('contact_name') }}"></div>
<div class="col-md-4"><label class="form-label">Телефон</label><input name="contact_phone" class="form-control" value="{{ old('contact_phone') }}"></div>
<div class="col-md-4"><label class="form-label">Email (необязательно)</label><input type="email" name="contact_email" class="form-control" value="{{ old('contact_email') }}"></div></div>
<div class="mb-3"><label class="form-label">Новые учащиеся — по одному ФИО на строку</label>
<textarea class="form-control" name="students_text" rows="7" placeholder="Иванов Иван Иванович&#10;Петров Пётр Сергеевич">{{ old('students_text') }}</textarea>
<div class="form-text">Учётные записи будут созданы только при зачислении. Для физлица можно оставить пустым — используется ФИО заявителя.</div></div>
<div class="mb-3"><label class="form-label">Или указать существующего учащегося</label>
<x-searchable-select name="user_id" url="{{ route('api.users.search') }}" placeholder="Начните вводить ФИО или логин..." :value="old('user_id')" />
<div class="form-text">Выберите аккаунт, если человек уже зарегистрирован. Можно сочетать с новыми учащимися (для предприятия).</div></div>
<div class="mb-3"><label class="form-label">Примечания</label><textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea></div>
<button class="btn btn-primary">Сохранить заявку</button><a href="{{ route('admin.training.intakes.show', $intake) }}" class="btn btn-outline-secondary">Отмена</a>
</div></form>
</main></div></div>
<script>
(function () {
    const applicant = document.getElementById('applicant-type');
    const enterprise = document.getElementById('enterprise-field');
    function sync() { enterprise.hidden = applicant.value !== 'organization'; }
    applicant.addEventListener('change', sync); sync();
})();
</script>
@endsection
