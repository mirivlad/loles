<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Сброс кэша разрешений
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Создаем разрешения
        $permissions = [
            // Пользователи
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            // Организации
            'organizations.view',
            'organizations.create',
            'organizations.edit',
            'organizations.delete',
            // Группы
            'groups.view',
            'groups.create',
            'groups.edit',
            'groups.delete',
            // Курсы
            'courses.view',
            'courses.create',
            'courses.edit',
            'courses.delete',
            'courses.publish',
            // Тесты
            'tests.view',
            'tests.create',
            'tests.edit',
            'tests.delete',
            // Назначения
            'assignments.view',
            'assignments.create',
            'assignments.edit',
            'assignments.delete',
            // Заявки
            'requests.view',
            'requests.approve',
            'requests.reject',
            // Отчеты
            'reports.view',
            'reports.export',
            // Откат результатов
            'results.rollback',
            // Системные настройки
            'settings.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Создаем роли
        $adminRole = Role::firstOrCreate(['name' => 'Administrator']);
        $adminRole->givePermissionTo(Permission::all());

        $managerRole = Role::firstOrCreate(['name' => 'Manager']);
        $managerRole->givePermissionTo(Permission::whereNotIn('name', ['settings.manage'])->get());

        $curatorRole = Role::firstOrCreate(['name' => 'Curator']);
        $curatorRole->givePermissionTo([
            'users.view', 'users.create', 'users.edit',
            'organizations.view',
            'groups.view', 'groups.create', 'groups.edit',
            'courses.view', 'courses.create', 'courses.edit',
            'tests.view', 'tests.create', 'tests.edit',
            'assignments.view', 'assignments.create', 'assignments.edit',
            'requests.view', 'requests.approve', 'requests.reject',
            'reports.view', 'reports.export',
            'results.rollback',
        ]);

        $studentRole = Role::firstOrCreate(['name' => 'Student']);
        $studentRole->givePermissionTo([
            'courses.view',
            'tests.view',
        ]);
    }
}
