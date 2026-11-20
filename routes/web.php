<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Admin\OrganizationController;
use App\Http\Controllers\Admin\GroupController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UserCredentialsController;
use App\Http\Controllers\Admin\CourseCategoryController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\CourseModuleController;
use App\Http\Controllers\Admin\CoursePackageController;
use App\Http\Controllers\Admin\TestController;
use App\Http\Controllers\Admin\TestCatalogController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\CourseAssignmentController;
use App\Http\Controllers\Admin\GroupUserController;
use App\Http\Controllers\Admin\CourseRequestController;
use App\Http\Controllers\Api\OrganizationSearchController;
use App\Http\Controllers\Api\GroupSearchController;
use App\Http\Controllers\Api\UserSearchController;
use App\Http\Controllers\Api\CourseSearchController;
use App\Http\Controllers\Student\CourseController as StudentCourseController;
use App\Http\Controllers\Student\TestController as StudentTestController;
use App\Http\Controllers\Student\ScheduleController as StudentScheduleController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Главная страница
Route::get('/', [HomeController::class, 'index'])->name('home');

// Маршруты аутентификации
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [LoginController::class, 'login'])->middleware('throttle:15,1');
    if (config('loles.public_registration')) {
        Route::get('register', [RegisterController::class, 'showRegistrationForm'])->name('register');
        Route::post('register', [RegisterController::class, 'register'])->middleware('throttle:5,1');
    }
});

// Маршруты для авторизованных пользователей
Route::middleware('auth')->group(function () {
    Route::post('logout', [LoginController::class, 'logout'])->name('logout');
    
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Администрирование
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::resource('organizations', OrganizationController::class)->except(['edit', 'update']);
        Route::resource('organizations.groups', GroupController::class);
        Route::get('/organizations/{organization}/edit', [OrganizationController::class, 'edit'])->name('organizations.edit');
        Route::put('/organizations/{organization}', [OrganizationController::class, 'update'])->name('organizations.update');
        Route::post('/organizations/{organization}/users/add', [OrganizationController::class, 'addUser'])->name('organizations.users.add');
        Route::delete('/organizations/{organization}/users/{user}/remove', [OrganizationController::class, 'removeUser'])->name('organizations.users.remove');
        Route::post('/organizations/{organization}/groups/add', [OrganizationController::class, 'addGroup'])->name('organizations.groups.add');
        Route::delete('/organizations/{organization}/groups/{group}/remove', [OrganizationController::class, 'removeGroup'])->name('organizations.groups.remove');
        Route::resource('course-requests', CourseRequestController::class);
        Route::post('/course-requests/{courseRequest}/approve', [CourseRequestController::class, 'approve'])->name('course-requests.approve');
        Route::post('/course-requests/{courseRequest}/reject', [CourseRequestController::class, 'reject'])->name('course-requests.reject');
        Route::post('/users/credentials/reveal', [UserCredentialsController::class, 'reveal'])
            ->middleware('throttle:30,1')->name('users.credentials.reveal');
        Route::post('/users/credentials/export', [UserCredentialsController::class, 'export'])
            ->middleware('throttle:10,1')->name('users.credentials.export');
        Route::post('/users/{user}/credentials/rotate', [UserCredentialsController::class, 'rotate'])
            ->middleware('throttle:10,1')->name('users.credentials.rotate');
        Route::resource('users', UserController::class);
        Route::resource('course-categories', CourseCategoryController::class);
        Route::get('/courses/{course}/preview', [CourseController::class, 'preview'])->name('courses.preview');
        Route::resource('courses', CourseController::class);
        Route::post('/courses/{course}/packages', [CoursePackageController::class, 'store'])->name('courses.packages.store');
        Route::get('/courses/{course}/packages/{package}/download', [CoursePackageController::class, 'download'])->name('courses.packages.download');
        Route::delete('/courses/{course}/packages/{package}', [CoursePackageController::class, 'destroy'])->name('courses.packages.destroy');
        Route::post('/courses/{course}/modules/{module}/move', [CourseModuleController::class, 'move'])->name('courses.modules.move');
        Route::get('/tests', [TestCatalogController::class, 'index'])->name('tests.index');
        Route::resource('courses.tests', TestController::class);
        Route::post('/courses/{course}/modules', [CourseModuleController::class, 'store'])->name('courses.modules.store');
        Route::put('/courses/{course}/modules/{module}', [CourseModuleController::class, 'update'])->name('courses.modules.update');
        Route::delete('/courses/{course}/modules/{module}', [CourseModuleController::class, 'destroy'])->name('courses.modules.destroy');
        Route::resource('tests.questions', QuestionController::class);
        Route::resource('course-assignments', CourseAssignmentController::class)->except(['show', 'edit', 'update']);
        Route::get('/course-assignments/{course}', CourseAssignmentController::class . '@show')->name('course-assignments.show');
        Route::resource('groups', GroupUserController::class)->except(['edit', 'update']);
        Route::get('/groups/{group}/edit', [GroupUserController::class, 'edit'])->name('groups.edit');
        Route::put('/groups/{group}', [GroupUserController::class, 'update'])->name('groups.update');
        Route::post('/groups/{group}/users/add', [GroupUserController::class, 'addUser'])->name('groups.users.add');
        Route::delete('/groups/{group}/users/{user}/remove', [GroupUserController::class, 'removeUser'])->name('groups.users.remove');
    });
    
    // API для поиска (требуется аутентификация)
    Route::middleware('auth')->group(function() {
        Route::get('/api/organizations/search', OrganizationSearchController::class)->name('api.organizations.search');
        Route::get('/api/groups/search', GroupSearchController::class)->name('api.groups.search');
        Route::get('/api/users/search', UserSearchController::class)->name('api.users.search');
        Route::get('/api/courses/search', CourseSearchController::class)->name('api.courses.search');
    });
    
    // Учебная часть (требуется аутентификация)
    Route::middleware('auth')->prefix('student')->name('student.')->group(function() {
        Route::get('/courses', [StudentCourseController::class, 'index'])->name('courses.index');
        Route::get('/courses/{course}', [StudentCourseController::class, 'show'])->name('courses.show');
        Route::get('/tests', [StudentTestController::class, 'index'])->name('tests.index');
        Route::get('/tests/{test}', [StudentTestController::class, 'show'])->name('tests.show');
        Route::get('/schedule', [StudentScheduleController::class, 'index'])->name('schedule.index');
        Route::get('/calendar', [StudentScheduleController::class, 'calendar'])->name('schedule.calendar');
    });
});
