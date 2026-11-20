@extends('layouts.app')
@section('title', 'Добавить пользователя')
@section('content')
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block sidebar">
            <div class="position-sticky pt-3">@include('partials._sidebar')</div>
        </nav>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-3 mb-3 border-bottom">
                <div>
                    <h1 class="h2 mb-1">Добавить учащегося или сотрудника</h1>
                    <p class="text-secondary mb-0">Для входа нужны только логин и пароль, почта не обязательна</p>
                </div>
                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Назад</a>
            </div>

            <form action="{{ route('admin.users.store') }}" method="POST" autocomplete="off" id="userCreateForm">
                @csrf
                <div class="row g-3">
                    <div class="col-lg-7">
                        <div class="card shadow-sm h-100">
                            <div class="card-body">
                                <h5>Данные пользователя</h5>
                                <div class="mb-3">
                                    <label for="newUserName" class="form-label">ФИО *</label>
                                    <input type="text" id="newUserName" name="name"
                                           class="form-control @error('name') is-invalid @enderror"
                                           value="{{ old('name') }}" autocomplete="off" required>
                                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="mb-3">
                                    <label for="newUsername" class="form-label">Логин</label>
                                    <input type="text" id="newUsername" name="username"
                                           class="form-control @error('username') is-invalid @enderror"
                                           value="{{ old('username') }}" placeholder="Создастся автоматически"
                                           autocomplete="off" autocapitalize="none" spellcheck="false" maxlength="255">
                                    <div class="form-text">Можно указать короткий логин или адрес почты, например ivanov@company.ru. Почта для входа не нужна — это просто имя пользователя. Если оставить пустым, логин будет создан автоматически.</div>
                                    @error('username')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                <div class="mb-3">
                                    <label for="newUserEmail" class="form-label">Email <span class="text-secondary">(необязательно)</span></label>
                                    <input type="email" id="newUserEmail" name="email"
                                           class="form-control @error('email') is-invalid @enderror"
                                           value="{{ old('email') }}" autocomplete="off">
                                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="mb-3">
                                    <label for="newUserPhone" class="form-label">Телефон <span class="text-secondary">(необязательно)</span></label>
                                    <input type="tel" id="newUserPhone" name="phone" value="{{ old('phone') }}"
                                           class="form-control" autocomplete="off">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="card shadow-sm mb-3">
                            <div class="card-body">
                                <h5>Обучение и доступ</h5>
                                <div class="mb-3">
                                    <label for="newUserRole" class="form-label">Роль *</label>
                                    <select name="role" id="newUserRole" class="form-select" required>
                                        @foreach($roles as $role)
                                            <option value="{{ $role }}" @selected(old('role', 'Student') === $role)>
                                                {{ \App\Support\UiLabels::role($role) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('role')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Предприятие</label>
                                    <x-searchable-select name="organization_id"
                                        url="{{ route('api.organizations.search') }}"
                                        placeholder="Начните вводить название предприятия..."
                                        :value="old('organization_id', $initialOrganization?->id)"
                                        :initial-label="$initialOrganization?->name" />
                                </div>
                                <div class="mb-3">
                                    <label for="newUserGroup" class="form-label">Учебная группа <span class="text-secondary">(необязательно)</span></label>
                                    <select name="group_id" id="newUserGroup" class="form-select @error('group_id') is-invalid @enderror">
                                        <option value="">Без группы</option>
                                        @foreach($allGroups as $group)
                                            <option value="{{ $group->id }}" @selected((string) old('group_id') === (string) $group->id)>
                                                {{ $group->name }} — {{ $group->organization?->name ?? 'Общая' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('group_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="form-check mb-3">
                                    <input type="checkbox" class="form-check-input" id="newUserActive" name="is_active" value="1"
                                           @checked(old('is_active', true))>
                                    <label for="newUserActive" class="form-check-label">Учётная запись активна</label>
                                </div>
                            </div>
                        </div>
                        <div class="card shadow-sm mb-3">
                            <div class="card-body">
                                <h5>Пароль для входа</h5>
                                <input type="hidden" name="generate_password" value="0">
                                <div class="form-check mb-3">
                                    <input type="checkbox" class="form-check-input" name="generate_password" id="generatePassword"
                                           value="1" @checked(old('generate_password', '1') == '1')>
                                    <label for="generatePassword" class="form-check-label">Сгенерировать автоматически</label>
                                </div>
                                <p class="small text-secondary">Для учащегося — 6 понятных букв и цифр. Логин и пароль можно скопировать из списка или выгрузить куратору.</p>
                                <div class="mb-3" id="generatedLengthField">
                                    <label for="generatedPasswordLength" class="form-label">Длина выдаваемого пароля</label>
                                    <select id="generatedPasswordLength" name="generated_password_length" class="form-select">
                                        <option value="6" @selected(old('generated_password_length', '6') == '6')>6 символов — проще ввести</option>
                                        <option value="8" @selected(old('generated_password_length') == '8')>8 символов — надёжнее</option>
                                    </select>
                                </div>
                                <div id="manualPasswordFields">
                                    <div class="mb-3">
                                        <label for="newUserPassword" class="form-label">Пароль</label>
                                        <input type="password" name="password" id="newUserPassword"
                                               class="form-control @error('password') is-invalid @enderror"
                                               autocomplete="new-password" minlength="6">
                                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div>
                                        <label for="newUserPasswordConfirmation" class="form-label">Повторите пароль</label>
                                        <input type="password" name="password_confirmation" id="newUserPasswordConfirmation"
                                               class="form-control" autocomplete="new-password" minlength="6">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="d-flex gap-2 my-4">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-person-check me-1"></i> Создать пользователя</button>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Отмена</a>
                </div>
            </form>
        </main>
    </div>
</div>
@endsection
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const role = document.getElementById('newUserRole');
    const generate = document.getElementById('generatePassword');
    const manual = document.getElementById('manualPasswordFields');
    const inputs = manual.querySelectorAll('input');
    const lengthField = document.getElementById('generatedLengthField');
    const lengthControl = document.getElementById('generatedPasswordLength');
    const update = () => {
        const student = role.value === 'Student';
        if (!student) {
            generate.checked = false;
        }
        generate.disabled = !student;
        const needsManual = !student || !generate.checked;
        lengthField.hidden = needsManual;
        lengthControl.disabled = needsManual;
        manual.hidden = !needsManual;
        inputs.forEach(input => {
            input.disabled = !needsManual;
            input.required = needsManual;
            input.minLength = student ? 6 : 12;
        });
    };
    role.addEventListener('change', update);
    generate.addEventListener('change', update);
    update();
});
</script>
@endpush
