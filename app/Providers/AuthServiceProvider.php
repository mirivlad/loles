<?php

namespace App\Providers;

use App\Models\Course;
use App\Models\CourseAssignment;
use App\Models\CourseRequest;
use App\Models\CourseCategory;
use App\Models\Question;
use App\Models\Test;
use App\Models\Group;
use App\Models\Organization;
use App\Models\User;
use App\Policies\CoursePolicy;
use App\Policies\CourseAssignmentPolicy;
use App\Policies\CourseRequestPolicy;
use App\Policies\CourseCategoryPolicy;
use App\Policies\QuestionPolicy;
use App\Policies\TestPolicy;
use App\Policies\GroupPolicy;
use App\Policies\OrganizationPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        CourseCategory::class => CourseCategoryPolicy::class,
        Course::class => CoursePolicy::class,
        CourseAssignment::class => CourseAssignmentPolicy::class,
        CourseRequest::class => CourseRequestPolicy::class,
        Test::class => TestPolicy::class,
        Question::class => QuestionPolicy::class,
        Organization::class => OrganizationPolicy::class,
        Group::class => GroupPolicy::class,
        User::class => UserPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}
