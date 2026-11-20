<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\TrainingEnrollment;
use App\Models\TrainingIntake;
use App\Services\TrainingOutcomeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class TrainingReportController extends Controller
{
    public function show(Request $request, TrainingIntake $intake, TrainingOutcomeService $service)
    {
        Gate::authorize('view', $intake);
        $filter = $this->filters($request);
        $intake->load(['courses.course', 'exams.test', 'groups']);
        $enrollments = $this->query($intake, $filter)
            ->with(['user', 'organization', 'group'])
            ->orderBy('id')->paginate(30)->withQueryString();
        $results = $service->summarize($intake, $enrollments->getCollection());
        $organizations = Organization::whereIn('id', $intake->enrollments()
            ->whereNotNull('organization_id')->select('organization_id'))
            ->orderBy('name')->get();
        return view('admin.training.reports.show', compact(
            'intake', 'enrollments', 'results', 'organizations', 'filter'
        ));
    }

    public function export(Request $request, TrainingIntake $intake, TrainingOutcomeService $service)
    {
        Gate::authorize('view', $intake);
        $filter = $this->filters($request);
        $intake->load(['courses.course', 'exams.test']);
        $required = $intake->exams->where('kind', 'official')->where('is_required', true);
        $query = $this->query($intake, $filter);
        $filename = 'loles-intake-' . $intake->id . '-outcomes.csv';

        return response()->streamDownload(function () use ($query, $intake, $service, $required) {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            $headers = [
                'Набор', 'Логин', 'ФИО', 'Предприятие при зачислении', 'Подгруппа',
                'Статус', 'Минимальное освоение курса, %', 'Обязательных экзаменов сдано', 'Причины',
            ];
            foreach ($intake->courses as $course) {
                $headers[] = 'Курс: ' . $course->course?->title . ', %';
            }
            foreach ($required as $exam) {
                $headers[] = 'Экзамен: ' . $exam->test?->title;
            }
            $this->csv($stream, $headers);
            $query->with(['user', 'organization', 'group'])->orderBy('id')
                ->chunkById(200, function ($batch) use ($stream, $service, $intake, $required) {
                    $results = $service->summarize($intake, $batch);
                    foreach ($batch as $enrollment) {
                        $state = $results[$enrollment->id];
                        $courseProgress = $state['course_progress'];
                        $row = [
                            $intake->name,
                            $enrollment->user?->username,
                            $enrollment->user?->name,
                            $enrollment->organization?->name ?? 'Физическое лицо',
                            $enrollment->group?->name ?? 'Общая',
                            $state['label'],
                            collect($courseProgress)->min() ?? 0,
                            $state['passed_exam_count'] . ' / ' . $state['required_exam_count'],
                            $state['reasons'],
                        ];
                        foreach ($intake->courses as $course) {
                            $row[] = $courseProgress[$course->course_id] ?? 0;
                        }
                        foreach ($required as $exam) {
                            $row[] = ($state['exam_results'][$exam->id] ?? false) ? 'Сдан' : 'Не сдан';
                        }
                        $this->csv($stream, $row);
                    }
                });
            fclose($stream);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filters(Request $request): array
    {
        return $request->validate([
            'scope' => ['nullable', Rule::in(['all', 'enterprise', 'individual'])],
            'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'group_id' => ['nullable', 'integer', 'exists:training_intake_groups,id'],
            'q' => ['nullable', 'string', 'max:120'],
        ]);
    }

    private function query(TrainingIntake $intake, array $filter)
    {
        return $intake->enrollments()
            ->when(($filter['scope'] ?? null) === 'enterprise',
                fn ($q) => $q->whereNotNull('organization_id'))
            ->when(($filter['scope'] ?? null) === 'individual',
                fn ($q) => $q->whereNull('organization_id'))
            ->when($filter['organization_id'] ?? null,
                fn ($q, $id) => $q->where('organization_id', $id))
            ->when($filter['group_id'] ?? null,
                fn ($q, $id) => $q->where('training_intake_group_id', $id))
            ->when($filter['q'] ?? null, fn ($q, $term) => $q->whereHas('user',
                fn ($u) => $u->where('name', 'like', '%' .
                    str_replace(['%', '_', '\\'], ['\\%', '\\_', '\\\\'], trim($term)) . '%')
                    ->orWhere('username', 'like', '%' .
                    str_replace(['%', '_', '\\'], ['\\%', '\\_', '\\\\'], trim($term)) . '%')));
    }

    private function csv($stream, array $row): void
    {
        // Prevent arbitrary learner/company names becoming executable formulas
        // when opened with Excel or LibreOffice.
        $safe = array_map(function ($value) {
            $string = (string) ($value ?? '');
            return preg_match('/^\s*[=+\-@]/u', $string) ? "'" . $string : $string;
        }, $row);
        fputcsv($stream, $safe, ';', '"', '');
    }
}
