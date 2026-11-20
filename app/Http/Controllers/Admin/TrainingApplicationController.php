<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\TrainingApplication;
use App\Models\TrainingIntake;
use App\Models\User;
use App\Services\TrainingEnrollmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TrainingApplicationController extends Controller
{
    public function create(TrainingIntake $intake)
    {
        Gate::authorize('update', $intake);
        abort_unless($intake->acceptsApplications(), 409, 'Приём заявок закрыт.');
        return view('admin.training.applications.create', compact('intake'));
    }

    public function store(Request $request, TrainingIntake $intake)
    {
        Gate::authorize('update', $intake);
        $data = $request->validate([
            'applicant_type' => ['required', Rule::in(['organization', 'individual'])],
            'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'contact_name' => ['required', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'students_text' => ['nullable', 'string', 'max:30000'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);
        if ($data['applicant_type'] === 'organization') {
            if (empty($data['organization_id']) ||
                !Organization::whereKey($data['organization_id'])->where('is_active', true)->exists()) {
                throw ValidationException::withMessages(['organization_id' => 'Выберите действующее предприятие.']);
            }
        } else {
            $data['organization_id'] = null;
        }

        $names = array_values(array_filter(array_map('trim',
            preg_split('/\R/u', $data['students_text'] ?? '') ?: []), fn ($name) => $name !== ''));
        if (count($names) > 500 || collect($names)->contains(fn ($name) => mb_strlen($name) > 255)) {
            throw ValidationException::withMessages(['students_text' => 'Максимум 500 учащихся; ФИО — не длиннее 255 символов.']);
        }
        $linked = null;
        if (!empty($data['user_id'])) {
            $linked = User::findOrFail($data['user_id']);
            if (!$linked->hasRole('Student') || !$linked->is_active) {
                throw ValidationException::withMessages(['user_id' => 'Выберите действующего учащегося.']);
            }
        }
        if (!$names && !$linked) {
            if ($data['applicant_type'] === 'individual') {
                $names = [trim($data['contact_name'])];
            } else {
                throw ValidationException::withMessages(['students_text' => 'Укажите хотя бы одного учащегося.']);
            }
        }
        if ($data['applicant_type'] === 'individual' && count($names) + (int) (bool) $linked !== 1) {
            throw ValidationException::withMessages(['students_text' => 'У физического лица должна быть одна запись на обучение.']);
        }

        $application = DB::transaction(function () use ($data, $intake, $request, $names, $linked) {
            $locked = TrainingIntake::query()->lockForUpdate()->findOrFail($intake->id);
            if (!$locked->acceptsApplications()) {
                throw ValidationException::withMessages(['training_intake_id' => 'Приём заявок закрыт.']);
            }
            $application = $locked->applications()->create([
                'applicant_type' => $data['applicant_type'],
                'organization_id' => $data['organization_id'],
                'contact_name' => $data['contact_name'],
                'contact_phone' => $data['contact_phone'] ?? null,
                'contact_email' => $data['contact_email'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $request->user()->id,
            ]);
            foreach ($names as $name) {
                $application->students()->create(['full_name' => $name]);
            }
            if ($linked) {
                $application->students()->create(['full_name' => $linked->name, 'user_id' => $linked->id]);
            }
            return $application;
        });
        return redirect()->route('admin.training.applications.show', [$intake, $application])
            ->with('success', 'Заявка принята. Доступ к курсам пока не предоставлен.');
    }

    public function show(TrainingIntake $intake, TrainingApplication $application)
    {
        Gate::authorize('view', $intake);
        $this->verifyParent($intake, $application);
        $application->load(['organization', 'students.user', 'students.enrollment']);
        $intake->load('groups');
        return view('admin.training.applications.show', compact('intake', 'application'));
    }

    public function review(Request $request, TrainingIntake $intake, TrainingApplication $application)
    {
        Gate::authorize('update', $intake);
        $this->verifyParent($intake, $application);
        $data = $request->validate(['decision' => ['required', Rule::in(['approved', 'rejected'])]]);
        DB::transaction(function () use ($application, $request, $data) {
            $locked = TrainingApplication::lockForUpdate()->findOrFail($application->id);
            if ($locked->status !== 'pending') {
                throw ValidationException::withMessages(['decision' => 'Рассматривать можно только ожидающую заявку.']);
            }
            $locked->update([
                'status' => $data['decision'],
                'approved_by' => $data['decision'] === 'approved' ? $request->user()->id : null,
                'approved_at' => $data['decision'] === 'approved' ? now() : null,
            ]);
        });
        return back()->with('success', 'Решение по заявке сохранено.');
    }

    public function payment(Request $request, TrainingIntake $intake, TrainingApplication $application)
    {
        Gate::authorize('update', $intake);
        $this->verifyParent($intake, $application);
        $data = $request->validate(['payment_status' => ['required', Rule::in(['unpaid', 'paid', 'exempt'])]]);
        DB::transaction(function () use ($application, $data) {
            $locked = TrainingApplication::lockForUpdate()->findOrFail($application->id);
            if ($locked->students()->whereHas('enrollment')->exists()) {
                throw ValidationException::withMessages(['payment_status' => 'Оплату зачисленной заявки изменять здесь нельзя.']);
            }
            $locked->update($data);
        });
        return back()->with('success', 'Статус оплаты обновлён.');
    }

    public function enroll(Request $request, TrainingIntake $intake, TrainingApplication $application,
        TrainingEnrollmentService $service)
    {
        Gate::authorize('update', $intake);
        $this->verifyParent($intake, $application);
        $data = $request->validate([
            'training_intake_group_id' => ['nullable', 'integer', 'exists:training_intake_groups,id'],
        ]);
        $count = $service->enroll($application, $request->user(), $data['training_intake_group_id'] ?? null);
        return redirect()->route('admin.training.applications.show', [$intake, $application])
            ->with('success', 'Зачислено учащихся: ' . $count . '. Логины и пароли — в разделе пользователей.');
    }

    private function verifyParent(TrainingIntake $intake, TrainingApplication $application): void
    {
        abort_unless($application->training_intake_id === $intake->id, 404);
    }
}
