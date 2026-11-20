<?php

namespace App\Services;

use App\Models\TrainingApplication;
use App\Models\TrainingEnrollment;
use App\Models\TrainingIntake;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TrainingEnrollmentService
{
    /**
     * All-or-nothing enrollment, with a row lock on the intake shared by
     * every enrollment transaction. No application student disappears on overflow.
     */
    public function enroll(TrainingApplication $application, User $actor, ?int $groupId = null): int
    {
        return DB::transaction(function () use ($application, $actor, $groupId) {
            $intake = TrainingIntake::query()->lockForUpdate()
                ->findOrFail($application->training_intake_id);
            $application = TrainingApplication::query()->lockForUpdate()->findOrFail($application->id);

            if (!in_array($intake->status, ['open', 'closed', 'training'], true)) {
                throw ValidationException::withMessages(['enrollment' => 'В этом состоянии набора зачисление закрыто.']);
            }
            if ($application->status !== 'approved' ||
                !in_array($application->payment_status, ['paid', 'exempt'], true)) {
                throw ValidationException::withMessages(['enrollment' => 'Требуется одобренная заявка и подтверждённая оплата (либо освобождение).']);
            }
            if ($groupId !== null && !$intake->groups()->whereKey($groupId)->exists()) {
                throw ValidationException::withMessages(['training_intake_group_id' => 'Группа не принадлежит этому набору.']);
            }

            $students = $application->students()->with('enrollment')->orderBy('id')->get()
                ->filter(fn ($student) => !$student->enrollment);
            if ($students->isEmpty()) {
                throw ValidationException::withMessages(['enrollment' => 'Все учащиеся из этой заявки уже зачислены.']);
            }
            $occupied = $intake->enrollments()->where('status', 'enrolled')->count();
            if ($occupied + $students->count() > $intake->capacity) {
                throw ValidationException::withMessages([
                    'enrollment' => 'Недостаточно мест: свободно ' . max(0, $intake->capacity - $occupied)
                        . ', ожидают ' . $students->count() . '. Заявка сохранена без изменений.',
                ]);
            }
            $userIds = $students->pluck('user_id')->filter();
            if ($userIds->unique()->count() !== $userIds->count() ||
                $intake->enrollments()->whereIn('user_id', $userIds)->exists()) {
                throw ValidationException::withMessages(['enrollment' => 'Учащийся уже числится в этом наборе. Проверьте заявку.']);
            }

            foreach ($students as $student) {
                $user = $student->user;
                if ($user) {
                    if (!$user->hasRole('Student') || !$user->is_active) {
                        throw ValidationException::withMessages(['enrollment' => 'Нельзя зачислить неактивного пользователя или сотрудника.']);
                    }
                } else {
                    $password = app(StudentCredentials::class)->generate();
                    $user = User::create([
                        'name' => $student->full_name,
                        'username' => 'pending' . Str::lower(Str::random(20)),
                        'password' => $password,
                        'email' => null,
                        'is_active' => true,
                    ]);
                    // The account has no global enterprise affiliation: enterprise
                    // at the time of enrollment belongs to the enrollment row.
                    $login = 's' . str_pad((string) $user->id, 5, '0', STR_PAD_LEFT);
                    if (User::where('username', $login)->whereKeyNot($user->id)->exists()) {
                        $login .= '_' . Str::lower(Str::random(5));
                    }
                    $user->update(['username' => $login]);
                    $user->assignRole('Student');
                    app(StudentCredentials::class)->issue($user, $password, $actor);
                    $student->update(['user_id' => $user->id]);
                }

                TrainingEnrollment::create([
                    'training_intake_id' => $intake->id,
                    'user_id' => $user->id,
                    'training_application_student_id' => $student->id,
                    'organization_id' => $application->organization_id,
                    'training_intake_group_id' => $groupId,
                    'status' => 'enrolled',
                    'enrolled_at' => now(),
                    'enrolled_by' => $actor->id,
                ]);
            }
            return $students->count();
        }, 3);
    }
}
