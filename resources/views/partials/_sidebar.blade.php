@php
    $currentRoute = request()->route()->getName();
@endphp

<ul class="nav flex-column">
    <li class="nav-item">
        <a class="nav-link {{ $currentRoute === 'dashboard' ? 'active' : '' }}" href="{{ route('dashboard') }}">
            <i class="bi bi-speedometer2"></i> Панель управления
        </a>
    </li>
    @if(Auth::user()->hasRole(['Administrator', 'Manager']))
    <li class="nav-item">
        <a class="nav-link {{ Str::startsWith($currentRoute, 'admin.organizations') ? 'active' : '' }}" href="{{ route('admin.organizations.index') }}">
            <i class="bi bi-building"></i> Организации
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ Str::startsWith($currentRoute, 'admin.users') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
            <i class="bi bi-people"></i> Пользователи
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ Str::startsWith($currentRoute, 'admin.course-categories') ? 'active' : '' }}" href="{{ route('admin.course-categories.index') }}">
            <i class="bi bi-folder"></i> Категории курсов
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="#">
            <i class="bi bi-book"></i> Курсы
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="#">
            <i class="bi bi-file-earmark-text"></i> Тесты
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="#">
            <i class="bi bi-calendar-check"></i> Назначения
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="#">
            <i class="bi bi-file-earmark-spreadsheet"></i> Отчёты
        </a>
    </li>
    @endif
    @if(Auth::user()->hasRole('Curator'))
    <li class="nav-item">
        <a class="nav-link" href="#">
            <i class="bi bi-people"></i> Пользователи
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="#">
            <i class="bi bi-people-fill"></i> Группы
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="#">
            <i class="bi bi-book"></i> Курсы
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="#">
            <i class="bi bi-calendar-check"></i> Назначения
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="#">
            <i class="bi bi-file-earmark-spreadsheet"></i> Отчёты
        </a>
    </li>
    @endif
    @if(Auth::user()->hasRole('Student'))
    <li class="nav-item">
        <a class="nav-link" href="#">
            <i class="bi bi-book"></i> Мои курсы
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="#">
            <i class="bi bi-file-earmark-text"></i> Тесты
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="#">
            <i class="bi bi-graph-up"></i> Прогресс
        </a>
    </li>
    @endif
</ul>
