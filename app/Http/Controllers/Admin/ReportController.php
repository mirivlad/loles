<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseAssignment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    private function filters(Request $request): array
    {
        return $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(['active', 'upcoming', 'expired', 'disabled'])],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);
    }

    private function assignments(array $filters): Builder
    {
        $today = today()->toDateString();

        return CourseAssignment::query()
            ->with(['course', 'user', 'group.organization', 'organization'])
            ->when(!empty($filters['q']), function (Builder $builder) use ($filters) {
                $term = '%' . str_replace(['%', '_', '\\'], ['\\%', '\\_', '\\\\'],
                    trim($filters['q'])) . '%';
                $builder->where(function (Builder $query) use ($term) {
                    $query->whereHas('course', fn (Builder $q) => $q->where('title', 'like', $term))
                        ->orWhereHas('user', fn (Builder $q) => $q->where('name', 'like', $term)
                            ->orWhere('username', 'like', $term))
                        ->orWhereHas('group', fn (Builder $q) => $q->where('name', 'like', $term))
                        ->orWhereHas('organization', fn (Builder $q) => $q->where('name', 'like', $term));
                });
            })
            ->when(!empty($filters['from']), fn (Builder $query) => $query->where('start_date', '>=', $filters['from']))
            ->when(!empty($filters['to']), fn (Builder $query) => $query->where('start_date', '<=', $filters['to']))
            ->when(!empty($filters['status']), function (Builder $query) use ($filters, $today) {
                switch ($filters['status']) {
                    case 'active':
                        $query->where('is_active', true)
                            ->where('start_date', '<=', $today)
                            ->where(fn (Builder $q) => $q->whereNull('end_date')
                                ->orWhere('end_date', '>=', $today));
                        break;
                    case 'upcoming':
                        $query->where('is_active', true)->where('start_date', '>', $today);
                        break;
                    case 'expired':
                        $query->where('is_active', true)->where('end_date', '<', $today);
                        break;
                    case 'disabled':
                        $query->where('is_active', false);
                        break;
                }
            });
    }

    public function index(Request $request)
    {
        Gate::authorize('viewAny', CourseAssignment::class);
        $filters = $this->filters($request);
        $assignments = $this->assignments($filters)
            ->orderByDesc('start_date')->orderByDesc('id')
            ->paginate(25)->withQueryString();

        return view('admin.reports.index', [
            'assignments' => $assignments,
            'filters' => $filters,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        Gate::authorize('viewAny', CourseAssignment::class);
        $filters = $this->filters($request);
        $name = 'loles-assignments-' . today()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($filters) {
            $output = fopen('php://output', 'w');
            // Excel recognizes UTF-8 CSV with BOM; keep semicolon for RU locales.
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, [
                'ID', 'Курс', 'Получатель', 'Тип', 'Начало', 'Окончание', 'Статус',
            ], ';');

            $query = $this->assignments($filters);
            $query->chunkById(250, function ($rows) use ($output) {
                foreach ($rows as $assignment) {
                    $recipient = match ($assignment->type) {
                        'individual' => $assignment->user?->name ?? 'Удалённый пользователь',
                        'group' => $assignment->group?->name ?? 'Удалённая группа',
                        'organization' => $assignment->organization?->name ?? 'Удалённое предприятие',
                        default => 'Неизвестный получатель',
                    };
                    $clean = fn ($value) => preg_match('/^[=+\-@\t\r]/u', trim((string) $value))
                        ? "'" . (string) $value : (string) $value;
                    fputcsv($output, [
                        $assignment->id,
                        $clean($assignment->course?->title ?? 'Неизвестный курс'),
                        $clean($recipient),
                        ['individual' => 'Учащийся', 'group' => 'Группа', 'organization' => 'Предприятие'][$assignment->type] ?? $assignment->type,
                        $assignment->start_date?->format('d.m.Y') ?? '',
                        $assignment->end_date?->format('d.m.Y') ?? '',
                        $assignment->statusLabel(),
                    ], ';');
                }
            });
            fclose($output);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
