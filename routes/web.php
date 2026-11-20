<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\CourseAttachmentController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Admin\OrganizationController;
use App\Http\Controllers\Admin\GroupController;
use App\Http\Controllers\Admin\TrainingProgramController;
use App\Http\Controllers\Admin\TrainingIntakeController;
use App\Http\Controllers\Admin\TrainingApplicationController;
use App\Http\Controllers\Admin\TrainingExamController;
use App\Http\Controllers\Admin\TrainingReportController;
use App\Http\Controllers\Admin\CuratorTrainingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UserCredentialsController;
use App\Http\Controllers\Admin\CourseCategoryController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\CourseModuleController;
use App\Http\Controllers\Admin\CoursePackageController;
use App\Http\Controllers\Admin\CourseLibraryController;
use App\Http\Controllers\Admin\TestController;
use App\Http\Controllers\Admin\TestCatalogController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\CourseAssignmentController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\GroupUserController;
use App\Http\Controllers\Admin\CourseRequestController;
use App\Http\Controllers\Api\OrganizationSearchController;
use App\Http\Controllers\Api\GroupSearchController;
use App\Http\Controllers\Api\UserSearchController;
use App\Http\Controllers\Api\CourseSearchController;
use App\Http\Controllers\Student\CourseController as StudentCourseController;
use App\Http\Controllers\Student\TrainingController as StudentTrainingController;
use App\Http\Controllers\Student\TrainingProgressController as StudentTrainingProgressController;
use App\Http\Controllers\Student\TestController as StudentTestController;
use App\Http\Controllers\Student\ExamController as StudentExamController;
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

    // Secure course assets: only course editors or enrolled learners can access them.
    Route::get('/course-assets/{course}/{attachment}/view', [CourseAttachmentController::class, 'view'])
        ->name('course.attachments.view');
    Route::get('/course-assets/{course}/{attachment}/download', [CourseAttachmentController::class, 'download'])
        ->name('course.attachments.download');
    
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Администрирование
    Route::prefix('admin')->name('admin.')->group(function () {
        // Intake-centered workflow. Legacy course assignments remain for migration.
        Route::prefix('training')->name('training.')->group(function () {
            Route::get('my-intakes', [CuratorTrainingController::class, 'index'])->name('curator.intakes');
            Route::resource('programs', TrainingProgramController::class)->except(['destroy']);
            Route::post('programs/{program}/courses', [TrainingProgramController::class, 'addCourse'])->name('programs.courses.add');
            Route::delete('programs/{program}/courses/{course}', [TrainingProgramController::class, 'removeCourse'])->name('programs.courses.remove');
            Route::resource('intakes', TrainingIntakeController::class)->except(['destroy']);
            Route::post('intakes/{intake}/groups', [TrainingIntakeController::class, 'addGroup'])->name('intakes.groups.add');
            Route::get('intakes/{intake}/report', [TrainingReportController::class, 'show'])->name('reports.show');
            Route::get('intakes/{intake}/report/export', [TrainingReportController::class, 'export'])->name('reports.export');
            Route::get('intakes/{intake}/exams/create', [TrainingExamController::class, 'create'])->name('exams.create');
            Route::post('intakes/{intake}/exams', [TrainingExamController::class, 'store'])->name('exams.store');
            Route::get('intakes/{intake}/exams/{exam}', [TrainingExamController::class, 'show'])->name('exams.show');
            Route::get('intakes/{intake}/exams/{exam}/edit', [TrainingExamController::class, 'edit'])->name('exams.edit');
            Route::put('intakes/{intake}/exams/{exam}', [TrainingExamController::class, 'update'])->name('exams.update');
            Route::post('intakes/{intake}/exams/{exam}/toggle', [TrainingExamController::class, 'toggle'])->name('exams.toggle');
            Route::get('intakes/{intake}/exams/{exam}/attempts/{attempt}', [TrainingExamController::class, 'attempt'])->name('exams.attempts.show');
            Route::post('intakes/{intake}/exams/{exam}/attempts/{attempt}/void', [TrainingExamController::class, 'void'])->name('exams.attempts.void');

            Route::post('intakes/{intake}/archive', [TrainingIntakeController::class, 'archive'])->name('intakes.archive');
            Route::put('intakes/{intake}/enrollments/{enrollment}/group', [TrainingIntakeController::class, 'updateEnrollmentGroup'])->name('intakes.enrollments.group');
            Route::get('intakes/{intake}/applications/create', [TrainingApplicationController::class, 'create'])->name('applications.create');
            Route::post('intakes/{intake}/applications', [TrainingApplicationController::class, 'store'])->name('applications.store');
            Route::get('intakes/{intake}/applications/{application}', [TrainingApplicationController::class, 'show'])->name('applications.show');
            Route::post('intakes/{intake}/applications/{application}/review', [TrainingApplicationController::class, 'review'])->name('applications.review');
            Route::post('intakes/{intake}/applications/{application}/payment', [TrainingApplicationController::class, 'payment'])->name('applications.payment');
            Route::post('intakes/{intake}/applications/{application}/enroll', [TrainingApplicationController::class, 'enroll'])->name('applications.enroll');
        });
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
        Route::get('/courses/{course}/preview/materials/{module}', [CourseController::class, 'previewMaterial'])
            ->name('courses.preview.material');
        Route::resource('courses', CourseController::class);
        Route::get('/courses/{course}/library/search', [CourseLibraryController::class, 'search'])->name('courses.library.search');
        Route::get('/courses/{course}/library/{package}/download', [CourseLibraryController::class, 'download'])->name('courses.library.download');
        Route::post('/courses/{course}/packages', [CoursePackageController::class, 'store'])->name('courses.packages.store');
        Route::get('/courses/{course}/packages/{package}/download', [CoursePackageController::class, 'download'])->name('courses.packages.download');
        Route::delete('/courses/{course}/packages/{package}', [CoursePackageController::class, 'destroy'])->name('courses.packages.destroy');
        Route::post('/courses/{course}/modules/{module}/move', [CourseModuleController::class, 'move'])->name('courses.modules.move');
        Route::get('/tests', [TestCatalogController::class, 'index'])->name('tests.index');
        Route::resource('courses.tests', TestController::class);
        Route::get('/courses/{course}/materials/create', [CourseModuleController::class, 'create'])
            ->name('courses.modules.create');
        Route::get('/courses/{course}/materials/{module}/edit', [CourseModuleController::class, 'edit'])
            ->name('courses.modules.edit');
        Route::get('/courses/{course}/attachments', [CourseAttachmentController::class, 'index'])
            ->name('courses.attachments.index');
        Route::post('/courses/{course}/attachments', [CourseAttachmentController::class, 'store'])
            ->middleware('throttle:90,1')->name('courses.attachments.store');
        Route::delete('/courses/{course}/attachments/{attachment}', [CourseAttachmentController::class, 'destroy'])
            ->name('courses.attachments.destroy');
        Route::post('/courses/{course}/modules', [CourseModuleController::class, 'store'])->name('courses.modules.store');
        Route::put('/courses/{course}/modules/{module}', [CourseModuleController::class, 'update'])->name('courses.modules.update');
        Route::delete('/courses/{course}/modules/{module}', [CourseModuleController::class, 'destroy'])->name('courses.modules.destroy');
        Route::resource('tests.questions', QuestionController::class);
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
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
        Route::get('/trainings', [StudentTrainingController::class, 'index'])->name('trainings.index');
        Route::post('/trainings/{enrollment}/courses/{course}/materials/{module}/complete',
            [StudentTrainingProgressController::class, 'complete'])
            ->middleware('throttle:60,1')->name('trainings.progress.complete');
        Route::get('/courses', [StudentCourseController::class, 'index'])->name('courses.index');
        Route::get('/courses/{course}', [StudentCourseController::class, 'show'])->name('courses.show');
        Route::get('/courses/{course}/materials/{module}', [StudentCourseController::class, 'material'])
            ->name('courses.material');
        Route::get('/exams', [StudentExamController::class, 'index'])->name('exams.index');
        Route::get('/exams/{exam}', [StudentExamController::class, 'show'])->name('exams.show');
        Route::post('/exams/{exam}/start', [StudentExamController::class, 'start'])
            ->middleware('throttle:30,1')->name('exams.start');
        Route::get('/exams/{exam}/attempts/{attempt}', [StudentExamController::class, 'attempt'])->name('exams.attempt.show');
        Route::post('/exams/{exam}/attempts/{attempt}', [StudentExamController::class, 'submit'])
            ->middleware('throttle:15,1')->name('exams.attempt.submit');
        Route::get('/tests', [StudentTestController::class, 'index'])->name('tests.index');
        Route::get('/tests/{test}', [StudentTestController::class, 'show'])->name('tests.show');
        Route::get('/schedule', [StudentScheduleController::class, 'index'])->name('schedule.index');
        Route::get('/calendar', [StudentScheduleController::class, 'calendar'])->name('schedule.calendar');
    });
});
