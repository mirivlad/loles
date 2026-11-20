<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>LoLeS - Система обучения</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Hero Section */
        .hero-section {
            background: var(--primary-gradient);
            color: white;
            padding: 100px 0;
            min-height: 80vh;
            display: flex;
            align-items: center;
        }

        .hero-section h1 {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }

        .hero-section p {
            font-size: 1.25rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }

        .btn-hero {
            padding: 15px 40px;
            font-size: 1.1rem;
            border-radius: 50px;
            margin: 0 10px;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .btn-hero:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }

        .btn-light-hero {
            background: white;
            color: #667eea;
            border: none;
        }

        .btn-outline-light-hero {
            background: transparent;
            color: white;
            border: 2px solid white;
        }

        .btn-outline-light-hero:hover {
            background: white;
            color: #667eea;
        }

        /* Features Section */
        .features-section {
            padding: 80px 0;
            background: #f8f9fa;
        }

        .feature-card {
            background: white;
            border: none;
            border-radius: 15px;
            padding: 40px 30px;
            text-align: center;
            transition: transform 0.3s, box-shadow 0.3s;
            height: 100%;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }

        .feature-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .feature-card h3 {
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: #333;
        }

        .feature-card p {
            color: #666;
            line-height: 1.6;
        }

        /* Courses Section */
        .courses-section {
            padding: 80px 0;
        }

        .section-title {
            text-align: center;
            margin-bottom: 50px;
        }

        .section-title h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .section-title p {
            color: #666;
            font-size: 1.1rem;
        }

        .course-card {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            height: 100%;
        }

        .course-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }

        .course-card img {
            height: 200px;
            object-fit: cover;
        }

        .course-card .card-body {
            padding: 25px;
        }

        .course-card .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .course-card .card-text {
            color: #666;
            margin-bottom: 15px;
        }

        /* CTA Section */
        .cta-section {
            background: var(--secondary-gradient);
            color: white;
            padding: 80px 0;
            text-align: center;
        }

        .cta-section h2 {
            font-size: 2.5rem;
            margin-bottom: 20px;
        }

        .cta-section p {
            font-size: 1.2rem;
            margin-bottom: 30px;
            opacity: 0.9;
        }

        /* Footer */
        footer {
            background: #2d3748;
            color: white;
            padding: 40px 0;
        }

        footer a {
            color: #a0aec0;
            text-decoration: none;
        }

        footer a:hover {
            color: white;
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1><i class="bi bi-mortarboard-fill"></i> LoLeS</h1>
                    <p>Современная платформа для онлайн-обучения и управления знаниями вашей организации</p>
                    <div class="mt-4">
                        @auth
                            <a href="{{ route('dashboard') }}" class="btn btn-light-hero btn-hero">
                                <i class="bi bi-speedometer2"></i> Перейти в кабинет
                            </a>
                        @else
                            <button class="btn btn-light-hero btn-hero" data-bs-toggle="modal" data-bs-target="#loginModal">
                                <i class="bi bi-box-arrow-in-right"></i> Войти
                            </button>
                            @if(config('loles.public_registration'))
                                <a href="{{ route('register') }}" class="btn btn-outline-light-hero btn-hero">
                                    <i class="bi bi-person-plus"></i> Регистрация
                                </a>
                            @endif
                        @endauth
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section">
        <div class="container">
            <div class="section-title">
                <h2>Возможности платформы</h2>
                <p>Всё необходимое для эффективного обучения</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-book"></i>
                        </div>
                        <h3>Онлайн-курсы</h3>
                        <p>Доступ к учебным материалам в любое время с любого устройства</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-file-earmark-text"></i>
                        </div>
                        <h3>Тестирование</h3>
                        <p>Проверка знаний с помощью тестов с различными типами вопросов</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-graph-up"></i>
                        </div>
                        <h3>Отслеживание прогресса</h3>
                        <p>Подробная статистика и аналитика по каждому обучающемуся</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Courses Section -->
    @if($courses->count() > 0)
    <section class="courses-section">
        <div class="container">
            <div class="section-title">
                <h2>Доступные курсы</h2>
                <p>Начните обучение прямо сейчас</p>
            </div>
            <div class="row g-4">
                @foreach($courses as $course)
                <div class="col-md-6 col-lg-4">
                    <div class="course-card card">
                        @if($course->image)
                        <img src="/storage/{{ $course->image }}" class="card-img-top" alt="{{ $course->title }}">
                        @else
                        <div style="height: 200px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-book" style="font-size: 4rem; color: white;"></i>
                        </div>
                        @endif
                        <div class="card-body">
                            @if($course->category)
                            <span class="badge bg-info mb-2">{{ $course->category->name }}</span>
                            @endif
                            <h5 class="card-title">{{ $course->title }}</h5>
                            <p class="card-text">{{ Str::limit($course->description, 100) }}</p>
                            @auth
                                <a href="{{ route('student.courses.show', $course) }}" class="btn btn-primary">
                                    <i class="bi bi-box-arrow-in-right"></i> Открыть курс
                                </a>
                            @else
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#loginModal">
                                    <i class="bi bi-box-arrow-in-right"></i> Войти для доступа
                                </button>
                            @endauth
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            <div class="text-center mt-5">
                @auth
                    <a href="{{ route('student.courses.index') }}" class="btn btn-outline-primary btn-lg">
                        <i class="bi bi-collection"></i> Все курсы
                    </a>
                @else
                    <button class="btn btn-outline-primary btn-lg" data-bs-toggle="modal" data-bs-target="#loginModal">
                        <i class="bi bi-box-arrow-in-right"></i> Войти для просмотра всех курсов
                    </button>
                @endauth
            </div>
        </div>
    </section>
    @endif

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <h2>Готовы начать обучение?</h2>
            <p>Для доступа к обучению получите логин и пароль у менеджера</p>
            @guest
                <a href="{{ config('loles.public_registration') ? route('register') : route('login') }}" class="btn btn-light btn-lg px-5">
                    <i class="bi bi-box-arrow-in-right"></i> {{ config('loles.public_registration') ? 'Зарегистрироваться' : 'Войти в систему' }}
                </a>
            @else
                <a href="{{ route('dashboard') }}" class="btn btn-light btn-lg px-5">
                    <i class="bi bi-speedometer2"></i> Перейти в кабинет
                </a>
            @endguest
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">© {{ date('Y') }} LoLeS. Все права защищены.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    @guest
                        <a href="#" data-bs-toggle="modal" data-bs-target="#loginModal">
                            <i class="bi bi-box-arrow-in-right"></i> Вход для сотрудников
                        </a>
                    @else
                        <a href="{{ route('dashboard') }}">
                            <i class="bi bi-speedometer2"></i> Кабинет
                        </a>
                    @endguest
                </div>
            </div>
        </div>
    </footer>

    @guest
    <!-- Login Modal -->
    <div class="modal fade" id="loginModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('login') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-box-arrow-in-right"></i> Вход в систему</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Логин</label>
                            <input type="text" name="username" class="form-control" autocomplete="username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Пароль</label>
                            <input type="password" name="password" class="form-control" autocomplete="current-password" required>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" name="remember" class="form-check-input" id="remember">
                            <label class="form-check-label" for="remember">Запомнить меня</label>
                        </div>
                        @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-primary">Войти</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endguest


</body>
</html>
