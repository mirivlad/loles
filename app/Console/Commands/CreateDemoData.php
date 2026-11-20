<?php

namespace App\Console\Commands;

use App\Models\Answer;
use App\Models\Course;
use App\Models\CourseAssignment;
use App\Models\CourseCategory;
use App\Models\CourseModule;
use App\Models\Group;
use App\Models\Organization;
use App\Models\Question;
use App\Models\Test;
use App\Models\User;
use App\Services\StudentCredentials;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreateDemoData extends Command
{
    protected $signature = 'loles:demo-data {--force : Explicitly allow demo data on a production instance}';
    protected $description = 'Add clearly labeled, repeatable examples of enterprises, groups, students, courses and tests';

    public function handle(StudentCredentials $credentials): int
    {
        if (app()->isProduction() && !$this->option('force')) {
            $this->error('This adds example student accounts. Re-run with --force to confirm.');
            return self::FAILURE;
        }

        DB::transaction(function () use ($credentials) {
            $north = Organization::firstOrCreate(['name' => 'ДЕМО — Северная вахта'],
                ['description' => 'Пример предприятия для обучения работников.', 'is_active' => true]);
            $plant = Organization::firstOrCreate(['name' => 'ДЕМО — Производственный участок'],
                ['description' => 'Второе примерное предприятие.', 'is_active' => true]);

            $firstGroup = Group::firstOrCreate(
                ['organization_id' => $north->id, 'name' => 'ДЕМО — Смена А'],
                ['is_active' => true, 'description' => 'Обучение сотрудников вахты']);
            $secondGroup = Group::firstOrCreate(
                ['organization_id' => $plant->id, 'name' => 'ДЕМО — Смена Б'],
                ['is_active' => true, 'description' => 'Подготовка сотрудников участка']);

            $students = [
                ['demo01', 'ДЕМО — Алексей Иванов', $north, $firstGroup],
                ['demo02', 'ДЕМО — Сергей Петров', $north, $firstGroup],
                ['demo03', 'ДЕМО — Николай Сидоров', $north, $firstGroup],
                ['demo04', 'ДЕМО — Михаил Орлов', $plant, $secondGroup],
                ['demo05', 'ДЕМО — Павел Смирнов', $plant, $secondGroup],
                ['demo06', 'ДЕМО — Андрей Волков', $plant, $secondGroup],
            ];

            foreach ($students as [$login, $name, $organization, $group]) {
                if (User::where('username', $login)->exists()) {
                    continue;
                }

                $password = $credentials->generate();
                $student = User::create([
                    'username' => $login,
                    'name' => $name,
                    'email' => null,
                    'password' => $password,
                    'organization_id' => $organization->id,
                    'is_active' => true,
                ]);
                $student->assignRole('Student');
                $student->groups()->syncWithoutDetaching([$group->id]);
                $credentials->issue($student, $password);
            }

            $category = CourseCategory::firstOrCreate(['slug' => 'loles-demo'],
                ['name' => 'ДЕМО — Примеры', 'description' => 'Образцы обучения', 'is_active' => true]);
            $course = Course::firstOrCreate(['slug' => 'loles-demo-introduction'], [
                'title' => 'ДЕМО — Вводный инструктаж',
                'category_id' => $category->id,
                'description' => 'Образец вводного курса для проверки интерфейса LoLeS.',
                'duration_minutes' => 30,
                'is_active' => true,
                'published_at' => now(),
            ]);

            CourseModule::firstOrCreate(
                ['course_id' => $course->id, 'title' => 'Основы безопасной работы'],
                ['type' => 'lesson', 'content' => '<p>Перед началом работы проверьте исправность оборудования и ознакомьтесь с инструкциями.</p>',
                    'sort_order' => 1, 'is_required' => true, 'is_active' => true]);

            $test = Test::firstOrCreate(['course_id' => $course->id, 'title' => 'ДЕМО — Проверка знаний'], [
                'description' => 'Пример теста после вводного инструктажа.',
                'type' => 'final', 'passing_score' => 70, 'is_active' => true,
            ]);
            $question = Question::firstOrCreate(['test_id' => $test->id,
                'question_text' => 'Что следует сделать при обнаружении неисправности оборудования?'], [
                'type' => 'multiple_choice', 'score' => 1, 'sort_order' => 1,
            ]);
            Answer::firstOrCreate(['question_id' => $question->id, 'answer_text' => 'Сообщить руководителю и не приступать к работе'],
                ['is_correct' => true, 'sort_order' => 1]);
            Answer::firstOrCreate(['question_id' => $question->id, 'answer_text' => 'Продолжить работу, если неисправность кажется незначительной'],
                ['is_correct' => false, 'sort_order' => 2]);

            foreach ([$firstGroup, $secondGroup] as $group) {
                CourseAssignment::firstOrCreate(
                    ['course_id' => $course->id, 'group_id' => $group->id, 'type' => 'group'],
                    ['organization_id' => $group->organization_id, 'start_date' => now()->toDateString(),
                        'is_active' => true, 'note' => 'ДЕМО: пример назначения группе']);
            }
        });

        $this->info('Демо-данные готовы. Для просмотра логинов и паролей используйте ведомость группы в интерфейсе менеджера.');
        $this->warn('Это учебные аккаунты: удалите демо-данные перед настоящей эксплуатацией.');
        return self::SUCCESS;
    }
}
