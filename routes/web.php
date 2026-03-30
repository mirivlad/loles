<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Admin\OrganizationController;
use App\Http\Controllers\Admin\GroupController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\CourseCategoryController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\TestController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\CourseAssignmentController;
use App\Http\Controllers\Admin\GroupUserController;
use App\Http\Controllers\Api\OrganizationSearchController;
use App\Http\Controllers\Api\GroupSearchController;
use App\Http\Controllers\Api\UserSearchController;
use App\Http\Controllers\Api\CourseSearchController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Главная страница
Route::get('/', function () {
    return view('welcome');
})->name('home');

// Маршруты аутентификации
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [LoginController::class, 'login']);
    Route::get('register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('register', [RegisterController::class, 'register']);
});

// Маршруты для авторизованных пользователей
Route::middleware('auth')->group(function () {
    Route::post('logout', [LoginController::class, 'logout'])->name('logout');
    
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Администрирование
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::resource('organizations', OrganizationController::class);
        Route::resource('organizations.groups', GroupController::class);
        Route::resource('users', UserController::class);
        Route::resource('course-categories', CourseCategoryController::class);
        Route::resource('courses', CourseController::class);
        Route::resource('courses.tests', TestController::class);
        Route::resource('tests.questions', QuestionController::class);
        Route::resource('course-assignments', CourseAssignmentController::class);
        Route::resource('groups', GroupUserController::class);
        Route::post('/users/{user}/groups/add', [GroupUserController::class, 'addUser'])->name('groups.users.add');
        Route::delete('/groups/{group}/users/{user}/remove', [GroupUserController::class, 'removeUser'])->name('groups.users.remove');
    });
    
    // API для поиска (требуется аутентификация)
    Route::middleware('auth')->group(function() {
        Route::get('/api/organizations/search', OrganizationSearchController::class)->name('api.organizations.search');
        Route::get('/api/groups/search', GroupSearchController::class)->name('api.groups.search');
        Route::get('/api/users/search', UserSearchController::class)->name('api.users.search');
        Route::get('/api/courses/search', CourseSearchController::class)->name('api.courses.search');
    });
});
