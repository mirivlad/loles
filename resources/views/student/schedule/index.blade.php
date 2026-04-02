@extends('layouts.app')
@section('title', 'Расписание')
@section('content')
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block sidebar"><div class="position-sticky pt-3">@include('partials._sidebar')</div></nav>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="bi bi-calendar3"></i> Расписание</h1>
                <div>
                    <a href="{{ route('student.schedule.calendar') }}" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-calendar-week"></i> Календарь
                    </a>
                </div>
            </div>
            
            <!-- Статистика -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card shadow-sm border-primary">
                        <div class="card-body text-center">
                            <div class="display-4 text-primary">{{ $stats['courses_count'] }}</div>
                            <div class="text-muted">Курсов</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm border-success">
                        <div class="card-body text-center">
                            <div class="display-4 text-success">{{ $stats['tests_count'] }}</div>
                            <div class="text-muted">Тестов</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm border-info">
                        <div class="card-body text-center">
                            <div class="display-4 text-info">{{ $stats['upcoming_tests'] }}</div>
                            <div class="text-muted">Предстоящих тестов</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Фильтры -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <form action="{{ route('student.schedule.index') }}" method="GET" class="row g-3">
                        <div class="col-md-4">
                            <select name="filter" class="form-select">
                                <option value="">Все события</option>
                                <option value="today" {{ request('filter') == 'today' ? 'selected' : '' }}>Сегодня</option>
                                <option value="week" {{ request('filter') == 'week' ? 'selected' : '' }}>Эта неделя</option>
                                <option value="month" {{ request('filter') == 'month' ? 'selected' : '' }}>Этот месяц</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Временная шкала -->
            <div class="card shadow-sm">
                <div class="card-header"><h5 class="mb-0"><i class="bi bi-clock-history"></i> Временная шкала</h5></div>
                <div class="card-body">
                    @if(count($schedule) > 0)
                    <div class="timeline">
                        @foreach($schedule as $date => $events)
                        <div class="mb-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="badge bg-primary rounded-pill px-3 py-2">
                                    <i class="bi bi-calendar-event"></i>
                                    {{ \Carbon\Carbon::parse($date)->format('d.m.Y') }}
                                    <small class="ms-2">({{ \Carbon\Carbon::parse($date)->diffForHumans() }})</small>
                                </div>
                            </div>
                            <div class="ms-4">
                                @foreach($events as $event)
                                <div class="card mb-2 border-0 shadow-sm">
                                    <div class="card-body py-2">
                                        <div class="d-flex align-items-center">
                                            <i class="bi {{ $event['icon'] }} fs-4 text-{{ $event['color'] }} me-3"></i>
                                            <div class="flex-grow-1">
                                                <a href="{{ $event['url'] }}" class="text-decoration-none">
                                                    <strong>{{ $event['title'] }}</strong>
                                                </a>
                                                <div class="text-muted small">{{ $event['description'] }}</div>
                                            </div>
                                            <span class="badge bg-{{ $event['color'] }}">{{ $event['type'] === 'course' ? 'Курс' : 'Тест' }}</span>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-calendar-x" style="font-size: 4rem;"></i>
                        <p class="mt-3">Событий пока нет</p>
                    </div>
                    @endif
                </div>
            </div>
            
            <!-- Список тестов -->
            @if($tests->count() > 0)
            <div class="card shadow-sm mt-4">
                <div class="card-header"><h5 class="mb-0"><i class="bi bi-file-earmark-text"></i> Доступные тесты</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Название</th>
                                    <th>Курс</th>
                                    <th>Вопросов</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tests as $test)
                                <tr>
                                    <td><strong>{{ $test->title }}</strong></td>
                                    <td>{{ $test->course->title }}</td>
                                    <td><span class="badge bg-info">{{ $test->questions->count() }}</span></td>
                                    <td>
                                        <a href="{{ route('student.tests.show', $test) }}" class="btn btn-primary btn-sm">
                                            <i class="bi bi-play"></i> Начать
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        </main>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}
.timeline::before {
    content: '';
    position: absolute;
    left: 10px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #dee2e6;
}
</style>
@endsection
