<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\StudentCredentials;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UserCredentialsController extends Controller
{
    private function authorizeStaff(): void
    {
        abort_unless(auth()->user()?->hasRole(['Administrator', 'Manager']), 403);
    }

    public function reveal(Request $request)
    {
        $this->authorizeStaff();

        $data = $request->validate([
            'user_ids' => ['required', 'array', 'min:1', 'max:50'],
            'user_ids.*' => ['integer', 'distinct'],
        ]);
        $students = User::role('Student')->with('issuedCredential')
            ->whereIn('id', $data['user_ids'])->get();

        Log::notice('Student credentials viewed', [
            'actor_id' => $request->user()->id,
            'user_ids' => $students->pluck('id')->all(),
        ]);

        return response()->json([
            'credentials' => $students->map(fn ($student) => [
                'id' => $student->id,
                'username' => $student->username,
                'password' => $student->issuedCredential?->secret,
            ])->all(),
        ])->header('Cache-Control', 'private, no-store')
            ->header('Pragma', 'no-cache');
    }

    public function export(Request $request)
    {
        $this->authorizeStaff();

        $validated = $request->validate([
            'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'group_id' => ['nullable', 'integer', 'exists:groups,id'],
            'search' => ['nullable', 'string', 'max:150'],
        ]);
        abort_unless($request->filled('organization_id') || $request->filled('group_id'),
            422, 'Для экспорта выберите предприятие или группу.');

        $query = User::role('Student');
        if ($request->filled('organization_id')) {
            $query->where('organization_id', $validated['organization_id']);
        }
        if ($request->filled('group_id')) {
            $query->whereHas('groups', fn ($q) => $q->where('groups.id', $validated['group_id']));
        }
        if ($request->filled('search')) {
            $search = trim((string) $validated['search']);
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('username', 'like', "%{$search}%"));
        }

        abort_if((clone $query)->count() > 2000, 422, 'Выберите меньшую группу или предприятие.');
        $students = $query->with(['issuedCredential', 'organization', 'groups'])
            ->orderBy('name')->get();

        Log::notice('Student credentials exported', [
            'actor_id' => $request->user()->id,
            'count' => $students->count(),
            'organization_id' => $request->input('organization_id'),
            'group_id' => $request->input('group_id'),
        ]);

        return response()->streamDownload(function () use ($students) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // Excel-compatible UTF-8 BOM
            fputcsv($out, ['ФИО', 'Логин', 'Пароль', 'Предприятие', 'Группы'], ';', '"', '');
            foreach ($students as $student) {
                $cells = [
                    $student->name, $student->username,
                    $student->issuedCredential?->secret ?? '(не сохранён)',
                    $student->organization?->name ?? '',
                    $student->groups->pluck('name')->implode(', '),
                ];
                // Prevent spreadsheet cells from being interpreted as formulas.
                $cells = array_map(fn ($value) =>
                    preg_match('/^[=+@\-\t\r]/u', (string) $value) ? "'" . $value : $value, $cells);
                fputcsv($out, $cells, ';', '"', '');
            }
            fclose($out);
        }, 'loles-students-' . now()->format('Ymd-His') . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function rotate(Request $request, User $user, StudentCredentials $credentials)
    {
        $this->authorizeStaff();
        Gate::authorize('update', $user);
        abort_unless($user->hasRole('Student'), 422);

        DB::transaction(function () use ($request, $user, $credentials) {
            $password = $credentials->generate();
            $user->update(['password' => $password, 'remember_token' => Str::random(60)]);
            $credentials->issue($user, $password, $request->user());
        });

        Log::notice('Student password changed by staff', [
            'actor_id' => $request->user()->id, 'student_id' => $user->id,
        ]);

        return back()->with('success', 'Пароль обновлён. Используйте «Показать пароли» для выдачи нового.');
    }
}
