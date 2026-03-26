<?php

namespace App\Providers;

use App\Models\CourseCategory;
use App\Models\Group;
use App\Models\Organization;
use App\Models\User;
use App\Policies\CourseCategoryPolicy;
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
