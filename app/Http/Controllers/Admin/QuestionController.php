<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\Test;
use App\Models\Answer;
use App\Models\QuestionMatchingPair;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

class QuestionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Test $test)
    {
        Gate::authorize('viewAny', Question::class);
        
        $questions = $test->questions()->with('answers')->orderBy('sort_order')->get();
        
        return view('admin.questions.index', compact('test', 'questions'));
    }

    public function create(Test $test)
    {
        Gate::authorize('create', Question::class);
        
        return view('admin.questions.create', compact('test'));
    }

    public function store(Request $request, Test $test)
    {
        Gate::authorize('create', Question::class);
        
        $validated = $request->validate([
            'type' => 'required|in:single_choice,multiple_choice,input,matching',
            'question_text' => 'required|string',
            'explanation' => 'nullable|string',
            'score' => 'nullable|integer|min:1',
            'sort_order' => 'nullable|integer',
            'is_required' => 'boolean',
            'answers' => 'nullable|array',
            'matching_pairs' => 'nullable|array',
        ]);
        
        DB::transaction(function () use ($test, $validated) {
            $question = $test->questions()->create([
                'type' => $validated['type'],
                'question_text' => $validated['question_text'],
                'explanation' => $validated['explanation'] ?? null,
                'score' => $validated['score'] ?? 1,
                'sort_order' => $validated['sort_order'] ?? $test->questions()->count(),
                'is_required' => $validated['is_required'] ?? true,
            ]);
            
            if (in_array($validated['type'], ['single_choice', 'multiple_choice']) && !empty($validated['answers'])) {
                foreach ($validated['answers'] as $answer) {
                    if (!empty($answer['text'])) {
                        $question->answers()->create([
                            'answer_text' => $answer['text'],
                            'is_correct' => $answer['is_correct'] ?? false,
                            'sort_order' => $answer['sort_order'] ?? 0,
                        ]);
                    }
                }
            }
            
            if ($validated['type'] === 'matching' && !empty($validated['matching_pairs'])) {
                foreach ($validated['matching_pairs'] as $pair) {
                    if (!empty($pair['left_text']) && !empty($pair['right_text'])) {
                        $question->matchingPairs()->create([
                            'left_text' => $pair['left_text'],
                            'right_text' => $pair['right_text'],
                            'match_score' => $pair['match_score'] ?? 1,
                            'sort_order' => $pair['sort_order'] ?? 0,
                        ]);
                    }
                }
            }
        });
        
        return redirect()->route('admin.tests.questions.index', $test)
            ->with('success', 'Вопрос успешно создан.');
    }

    public function show(Test $test, Question $question)
    {
        Gate::authorize('view', $question);
        return view('admin.questions.show', compact('test', 'question'));
    }

    public function edit(Test $test, Question $question)
    {
        Gate::authorize('update', $question);
        $question->load(['answers', 'matchingPairs']);
        return view('admin.questions.edit', compact('test', 'question'));
    }

    public function update(Request $request, Test $test, Question $question)
    {
        Gate::authorize('update', $question);
        
        $validated = $request->validate([
            'type' => 'required|in:single_choice,multiple_choice,input,matching',
            'question_text' => 'required|string',
            'explanation' => 'nullable|string',
            'score' => 'nullable|integer|min:1',
            'sort_order' => 'nullable|integer',
            'is_required' => 'boolean',
            'answers' => 'nullable|array',
            'matching_pairs' => 'nullable|array',
        ]);
        
        DB::transaction(function () use ($question, $validated) {
            $question->update([
                'type' => $validated['type'],
                'question_text' => $validated['question_text'],
                'explanation' => $validated['explanation'] ?? null,
                'score' => $validated['score'] ?? 1,
                'sort_order' => $validated['sort_order'] ?? $question->sort_order,
                'is_required' => $validated['is_required'] ?? true,
            ]);
            
            if (in_array($validated['type'], ['single_choice', 'multiple_choice']) && !empty($validated['answers'])) {
                $question->answers()->delete();
                foreach ($validated['answers'] as $answer) {
                    if (!empty($answer['text'])) {
                        $question->answers()->create([
                            'answer_text' => $answer['text'],
                            'is_correct' => $answer['is_correct'] ?? false,
                            'sort_order' => $answer['sort_order'] ?? 0,
                        ]);
                    }
                }
            }
            
            if ($validated['type'] === 'matching' && !empty($validated['matching_pairs'])) {
                $question->matchingPairs()->delete();
                foreach ($validated['matching_pairs'] as $pair) {
                    if (!empty($pair['left_text']) && !empty($pair['right_text'])) {
                        $question->matchingPairs()->create([
                            'left_text' => $pair['left_text'],
                            'right_text' => $pair['right_text'],
                            'match_score' => $pair['match_score'] ?? 1,
                            'sort_order' => $pair['sort_order'] ?? 0,
                        ]);
                    }
                }
            }
        });
        
        return redirect()->route('admin.tests.questions.index', $test)
            ->with('success', 'Вопрос успешно обновлён.');
    }

    public function destroy(Test $test, Question $question)
    {
        Gate::authorize('delete', $question);
        $question->delete();
        return redirect()->route('admin.tests.questions.index', $test)
            ->with('success', 'Вопрос успешно удалён.');
    }
}
