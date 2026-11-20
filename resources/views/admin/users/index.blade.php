@extends('layouts.app')
@section('title', 'Учащиеся и сотрудники')
@section('content')
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
            <div class="position-sticky pt-3">@include('partials._sidebar')</div>
        </nav>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between flex-wrap align-items-center gap-2 pt-3 pb-3 mb-3 border-bottom">
                <div>
                    <h1 class="h2 mb-1">Учащиеся и сотрудники</h1>
                    <p class="text-secondary mb-0">Поиск, фильтрация и управление учётными записями</p>
                </div>
                @can('create', App\Models\User::class)
                    <a href="{{ route('admin.users.create', request()->only('organization_id')) }}" class="btn btn-primary">
                        <i class="bi bi-person-plus me-1"></i> Добавить пользователя
                    </a>
                @endcan
            </div>

            @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="alert alert-danger" role="alert">{{ session('error') }}</div>@endif

            <div class="row g-3 mb-4">
                <div class="col-md-4"><div class="manager-summary h-100">
                    <div class="manager-summary__value">{{ number_format($totalUsers, 0, ',', ' ') }}</div>
                    <div class="manager-summary__label">Всего пользователей</div>
                </div></div>
                <div class="col-md-4"><div class="manager-summary h-100">
                    <div class="manager-summary__value">{{ number_format($activeUsers, 0, ',', ' ') }}</div>
                    <div class="manager-summary__label">Активных аккаунтов</div>
                </div></div>
                <div class="col-md-4"><div class="manager-summary h-100">
                    <div class="manager-summary__value">{{ number_format($unassignedUsers, 0, ',', ' ') }}</div>
                    <div class="manager-summary__label">Без предприятия</div>
                </div></div>
            </div>

            <form action="{{ route('admin.users.index') }}" method="GET" class="manager-toolbar mb-3" aria-label="Фильтры пользователей">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-3 col-md-6">
                        <label for="userSearch" class="form-label">ФИО, логин, email или телефон</label>
                        <input id="userSearch" type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Найти пользователя…">
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <label for="userOrganization" class="form-label">Предприятие</label>
                        <select id="userOrganization" name="organization_id" class="form-select">
                            <option value="">Все предприятия</option>
                            @foreach($organizations as $id => $name)
                                <option value="{{ $id }}" @selected((string) request('organization_id') === (string) $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-4">
                        <label for="userGroup" class="form-label">Учебная группа</label>
                        <select id="userGroup" name="group_id" class="form-select">
                            <option value="">Все группы</option>
                            @foreach($groups as $id => $name)
                                <option value="{{ $id }}" @selected((string) request('group_id') === (string) $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-4">
                        <label for="userRole" class="form-label">Роль</label>
                        <select id="userRole" name="role" class="form-select">
                            <option value="">Все роли</option>
                            @foreach($roles as $role)
                                <option value="{{ $role }}" @selected(request('role') === $role)>{{ \App\Support\UiLabels::role($role) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-4">
                        <label for="userStatus" class="form-label">Статус</label>
                        <select id="userStatus" name="status" class="form-select">
                            <option value="">Любой</option>
                            <option value="active" @selected(request('status') === 'active')>Активен</option>
                            <option value="inactive" @selected(request('status') === 'inactive')>Неактивен</option>
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1" title="Применить фильтры"><i class="bi bi-search"></i> Найти</button>
                        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary" title="Сбросить фильтры" aria-label="Сбросить фильтры"><i class="bi bi-x-lg"></i></a>
                    </div>
                </div>
            </form>

            @if(auth()->user()->hasRole(['Administrator', 'Manager']))
                <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                    <button id="showStudentCredentials" type="button" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-eye"></i> Показать пароли на этой странице
                    </button>
                    @if(request()->filled('organization_id') || request()->filled('group_id'))
                        <x-credentials-export
                            :organization-id="request('organization_id')"
                            :group-id="request('group_id')"
                            :search="request('search')" />
                    @else
                        <span class="text-secondary small">Для скачивания ведомости выберите предприятие или учебную группу.</span>
                    @endif
                </div>
            @endif
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="small text-secondary">Найдено: {{ $users->total() }}</span>
                <span class="small text-secondary">Показано {{ $users->count() }} на странице</span>
            </div>
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover manager-table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Пользователь</th>
                                    <th>Предприятие</th>
                                    <th>Роль</th>
                                    <th>Статус</th>
                                    @if(auth()->user()->hasRole(['Administrator', 'Manager']))
                                        <th>Пароль учащегося</th>
                                    @endif
                                    <th class="text-end pe-3">Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($users as $user)
                                    <tr>
                                        <td class="ps-3">
                                            <a class="fw-semibold text-decoration-none" href="{{ route('admin.users.show', $user) }}">{{ $user->name }}</a>
                                            <div class="small"><code>{{ $user->username }}</code></div>
                                            @if($user->email)<div class="small text-secondary">{{ $user->email }}</div>@endif
                                            @if($user->phone)<div class="small text-secondary">{{ $user->phone }}</div>@endif
                                        </td>
                                        <td>
                                            @if($user->organization)
                                                <a href="{{ route('admin.organizations.show', $user->organization) }}" class="text-decoration-none">{{ $user->organization->name }}</a>
                                            @else
                                                <span class="text-secondary">Не указано</span>
                                            @endif
                                        </td>
                                        <td><span class="badge bg-info text-dark">{{ \App\Support\UiLabels::role($user->getRoleNames()->first()) }}</span></td>
                                        <td><span class="badge {{ $user->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $user->is_active ? 'Активен' : 'Неактивен' }}</span></td>
                                        @if(auth()->user()->hasRole(['Administrator', 'Manager']))
                                            <td class="student-password" data-student-id="{{ $user->hasRole('Student') ? $user->id : '' }}">
                                                @if($user->hasRole('Student'))
                                                    <span class="text-secondary">{{ $user->issued_credential_exists ? 'Скрыт' : 'Не сохранён' }}</span>
                                                @else — @endif
                                            </td>
                                        @endif
                                        <td class="text-end pe-3">
                                            <div class="btn-group btn-group-sm" role="group" aria-label="Действия для {{ $user->name }}">
                                                <a class="btn btn-outline-primary" href="{{ route('admin.users.show', $user) }}" title="Просмотр" aria-label="Просмотреть {{ $user->name }}"><i class="bi bi-eye"></i></a>
                                                @can('update', $user)
                                                    <a class="btn btn-outline-secondary" href="{{ route('admin.users.edit', $user) }}" title="Изменить" aria-label="Изменить {{ $user->name }}"><i class="bi bi-pencil"></i></a>
                                                @endcan
                                                @if(auth()->user()->hasRole(['Administrator', 'Manager']) && $user->hasRole('Student'))
                                                    <form method="POST" action="{{ route('admin.users.credentials.rotate', $user) }}"
                                                          onsubmit="return confirm('Создать новый пароль учащемуся? Старый перестанет работать.')">
                                                        @csrf
                                                        <button type="submit" class="btn btn-outline-warning" title="Новый пароль" aria-label="Сменить пароль {{ $user->name }}"><i class="bi bi-key"></i></button>
                                                    </form>
                                                @endif
                                                @can('delete', $user)
                                                    <form action="{{ route('admin.users.destroy', $user) }}" method="POST" onsubmit="return confirm('Удалить пользователя?')">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="btn btn-outline-danger" title="Удалить" aria-label="Удалить {{ $user->name }}"><i class="bi bi-trash"></i></button>
                                                    </form>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="{{ auth()->user()->hasRole(['Administrator', 'Manager']) ? 6 : 5 }}">
                                        <div class="manager-empty">
                                            <i class="bi bi-people fs-2 d-block mb-2"></i>
                                            <strong>Никого не найдено</strong>
                                            <p class="mb-2">Попробуйте изменить фильтры или добавьте первого пользователя.</p>
                                            <a href="{{ route('admin.users.index') }}">Очистить фильтры</a>
                                        </div>
                                    </td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="mt-3">{{ $users->links() }}</div>
        </main>
    </div>
</div>
@endsection
@if(auth()->user()->hasRole(['Administrator', 'Manager']))
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.getElementById('showStudentCredentials');
    if (!toggle) return;
    let revealed = false;
    const cells = [...document.querySelectorAll('.student-password[data-student-id]')]
        .filter(cell => cell.dataset.studentId);

    const conceal = () => {
        cells.forEach(cell => {
            cell.textContent = 'Скрыт';
        });
        revealed = false;
        toggle.textContent = 'Показать пароли на этой странице';
    };

    toggle.addEventListener('click', async () => {
        if (revealed) { conceal(); return; }
        if (cells.length === 0) {
            alert('На этой странице нет учащихся.');
            return;
        }

        toggle.disabled = true;
        try {
            const response = await fetch(@json(route('admin.users.credentials.reveal')), {
                method: 'POST',
                cache: 'no-store',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({user_ids: cells.map(cell => Number(cell.dataset.studentId))})
            });
            if (!response.ok) throw new Error('Не удалось получить реквизиты.');
            const data = await response.json();
            const byId = new Map(data.credentials.map(item => [String(item.id), item]));
            cells.forEach(cell => {
                cell.textContent = '';
                const entry = byId.get(cell.dataset.studentId);
                if (!entry || !entry.password) {
                    cell.textContent = 'Не сохранён';
                    return;
                }
                const value = document.createElement('code');
                value.textContent = entry.password;
                const copy = document.createElement('button');
                copy.type = 'button';
                copy.className = 'btn btn-outline-secondary btn-sm ms-2';
                copy.title = 'Скопировать пароль';
                copy.textContent = 'Копировать';
                copy.addEventListener('click', () => navigator.clipboard.writeText(entry.password));
                cell.append(value, copy);
            });
            revealed = true;
            toggle.textContent = 'Скрыть пароли';
        } catch (error) {
            alert(error.message);
        } finally {
            toggle.disabled = false;
        }
    });
});
</script>
@endpush
@endif
