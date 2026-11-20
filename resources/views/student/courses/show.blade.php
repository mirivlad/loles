@extends('layouts.app')
@section('title', $course->title)
@section('content')
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
            <div class="position-sticky pt-3">@include('partials._sidebar')</div>
        </nav>
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 pt-3 pb-3 mb-4 border-bottom">
                <div>
                    <h1 class="h2 mb-1">{{ $course->title }}</h1>
                    <p class="text-secondary mb-0">
                        {{ $course->category?->name ?? 'Курс обучения' }}
                        @if($course->duration_minutes) · Примерно {{ $course->duration_minutes }} мин.@endif
                    </p>
                </div>
                @if($preview ?? false)
                    <a href="{{ route('admin.courses.show', $course) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Вернуться к редактору</a>
                @else
                    <a href="{{ route('student.courses.index') }}" class="btn btn-outline-secondary">К моим курсам</a>
                @endif
            </div>
            @if($preview ?? false)
                <div class="alert alert-info" role="status"><i class="bi bi-eye me-1"></i>
                    Предпросмотр. Это вид учащегося, но просмотр не записывается как прохождение курса.
                </div>
            @endif
            @if($course->thumbnail)
                <img src="{{ asset('storage/' . $course->thumbnail) }}" alt="Обложка курса"
                     class="img-fluid rounded-3 mb-4" style="max-height:230px;object-fit:cover">
            @endif
            @if($course->description)
                <div class="card shadow-sm mb-4"><div class="card-body">
                    <h2 class="h5">О курсе</h2>
                    <p class="mb-0">{{ $course->description }}</p>
                    @if($course->objectives)
                        <h3 class="h6 mt-3">После обучения вы сможете</h3>
                        <p class="mb-0">{{ $course->objectives }}</p>
                    @endif
                </div></div>
            @endif
            <h2 class="h4 mb-3">Что предстоит изучить</h2>
            @forelse($course->modules->whereNull('parent_id')->where('is_active', true)->sortBy('sort_order') as $module)
                @include('student.courses._learning_item', ['item' => $module, 'depth' => 0])
                @if($module->type === 'section')
                    @foreach($module->children->where('is_active', true)->sortBy('sort_order') as $child)
                        @include('student.courses._learning_item', ['item' => $child, 'depth' => 1])
                    @endforeach
                @endif
            @empty
                <div class="text-secondary border rounded-3 p-4 mb-4">
                    Пока нет учебных материалов. Преподаватель дополнит этот курс.
                </div>
            @endforelse

            @if($course->tests->where('is_active', true)->isNotEmpty())
                <div class="card shadow-sm mb-4">
                    <div class="card-header"><h2 class="h5 mb-0">Проверка знаний</h2></div>
                    <div class="list-group list-group-flush">
                        @foreach($course->tests->where('is_active', true) as $test)
                            <div class="list-group-item d-flex justify-content-between align-items-center gap-2">
                                <div>
                                    <strong>{{ $test->title }}</strong>
                                    <div class="small text-secondary">Проходной балл: {{ $test->passing_score }}%</div>
                                </div>
                                @if(!($preview ?? false))
                                    <a class="btn btn-outline-primary btn-sm" href="{{ route('student.tests.show', $test) }}">Открыть</a>
                                @else
                                    <span class="text-secondary small">Только просмотр</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if($course->packages->isNotEmpty())
                <div class="alert alert-warning small">
                    В курс добавлены пакеты SCORM/H5P. Они ещё не доступны для прохождения:
                    воспроизведение и учёт результатов будут подключены отдельным этапом.
                </div>
            @endif
        </main>
    </div>
</div>
@endsection
