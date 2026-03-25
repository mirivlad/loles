<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Organization;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Создаем администратора
        $admin = User::firstOrCreate(
            ['email' => 'admin@lms.local'],
            [
                'name' => 'Администратор',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $admin->assignRole('Administrator');

        // Создаем менеджера
        $manager = User::firstOrCreate(
            ['email' => 'manager@lms.local'],
            [
                'name' => 'Менеджер',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $manager->assignRole('Manager');

        // Создаем организацию
        $organization = Organization::firstOrCreate(
            ['name' => 'Тестовая организация'],
            [
                'inn' => '1234567890',
                'kpp' => '123456789',
                'address' => 'г. Москва, ул. Тестовая, д. 1',
                'phone' => '+7 (999) 123-45-67',
                'email' => 'info@test-org.local',
                'description' => 'Тестовая организация для демонстрации',
            ]
        );

        // Создаем куратора
        $curator = User::firstOrCreate(
            ['email' => 'curator@lms.local'],
            [
                'name' => 'Куратор',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'organization_id' => $organization->id,
            ]
        );
        $curator->assignRole('Curator');

        // Создаем учащихся
        for ($i = 1; $i <= 10; $i++) {
            $student = User::firstOrCreate(
                ['email' => "student{$i}@lms.local"],
                [
                    'name' => "Учащийся {$i}",
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'organization_id' => $i <= 5 ? $organization->id : null,
                ]
            );
            $student->assignRole('Student');
        }
    }
}
