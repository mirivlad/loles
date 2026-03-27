<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\Test;
use App\Models\Answer;
use App\Models\QuestionMatchingPair;
use App\Models\QuestionOrderingItem;
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
        
        $questions = $test->questions()->with(['answers', 'matchingPairs', 'orderingItems'])->orderBy('sort_order')->get();
        
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

        \Log::info('=== QUESTION STORE DEBUG ===');
        \Log::info('Request type: ' . $request->input('type'));
        \Log::info('Request answers: ' . json_encode($request->input('answers', [])));
        
        // Проверяем каждый ответ
        $hasAnyFile = false;
        foreach ($request->input('answers', []) as $index => $answer) {
            \Log::info("Answer $index: text=" . ($answer['text'] ?? 'NULL') . ", is_correct=" . ($answer['is_correct'] ?? 'NOT SET'));
            $hasFile = $request->hasFile("answers.$index.image");
            \Log::info("Answer $index has file: " . ($hasFile ? 'YES' : 'NO'));
            if ($hasFile) {
                $hasAnyFile = true;
                $file = $request->file("answers.$index.image");
                \Log::info("File info: " . $file->getClientOriginalName() . ", " . $file->getSize() . " bytes");
            }
        }
        \Log::info('Has any files: ' . ($hasAnyFile ? 'YES' : 'NO'));

        $validated = $request->validate([
            'type' => 'required|in:multiple_choice,matching,ordering',
            'question_text' => 'required|string',
            'explanation' => 'nullable|string',
            'score' => 'nullable|integer|min:1',
            'sort_order' => 'nullable|integer',
            'is_required' => 'boolean',
            'answers' => 'nullable|array',
            'answers.*.text' => 'nullable|string',
            'matching_pairs' => 'nullable|array',
            'ordering_items' => 'nullable|array',
        ]);
        
        // Валидация картинок вручную
        foreach ($request->input('answers', []) as $index => $answer) {
            if ($request->hasFile("answers.$index.image")) {
                $request->validate([
                    "answers.$index.image" => 'image|max:2048',
                ], [
                    "answers.$index.image.image" => "Ответ #$index: файл должен быть изображением",
                    "answers.$index.image.max" => "Ответ #$index: файл не должен превышать 2MB",
                ]);
            }
        }
        
        // Проверка что есть хотя бы текст или картинка в ответах
        if ($validated['type'] === 'multiple_choice' && !empty($validated['answers'])) {
            $hasValidAnswer = false;
            foreach ($request->input('answers', []) as $index => $answer) {
                $hasText = !empty($answer['text']);
                $hasImage = $request->hasFile("answers.$index.image");
                \Log::info("Checking answer $index: hasText=" . ($hasText ? 'YES' : 'NO') . ", hasImage=" . ($hasImage ? 'YES' : 'NO'));
                if ($hasText || $hasImage) {
                    $hasValidAnswer = true;
                    break;
                }
            }
            \Log::info('Has valid answer: ' . ($hasValidAnswer ? 'YES' : 'NO'));
            if (!$hasValidAnswer) {
                \Log::info('No valid answers - redirecting back');
                return back()->withErrors(['answers' => 'Добавьте хотя бы один ответ (текст или картинку)'])->withInput();
            }
        }
        
        DB::transaction(function () use ($test, $validated, $request) {
            $question = $test->questions()->create([
                'type' => $validated['type'],
                'question_text' => $validated['question_text'],
                'explanation' => $validated['explanation'] ?? null,
                'score' => $validated['score'] ?? 1,
                'sort_order' => $validated['sort_order'] ?? $test->questions()->count(),
                'is_required' => $validated['is_required'] ?? true,
            ]);

            // Ответы для multiple_choice
            if ($validated['type'] === 'multiple_choice' && !empty($validated['answers'])) {
                foreach ($request->input('answers', []) as $index => $answer) {
                    $hasText = !empty($answer['text']);
                    $hasImage = $request->hasFile("answers.$index.image");
                    $isCorrect = isset($answer['is_correct']) && $answer['is_correct'] === '1';
                    
                    \Log::info("Saving answer $index: hasText=" . ($hasText ? 'YES' : 'NO') . ", hasImage=" . ($hasImage ? 'YES' : 'NO') . ", isCorrect=" . ($isCorrect ? 'YES' : 'NO'));
                    
                    // Сохраняем если есть текст или картинка
                    if ($hasText || $hasImage) {
                        $imagePath = null;
                        if ($hasImage) {
                            $file = $request->file("answers.$index.image");
                            \Log::info("File object: " . ($file ? 'EXISTS' : 'NULL'));
                            if ($file) {
                                $imagePath = $file->store('questions/answers', 'public');
                                \Log::info("Saved image to: $imagePath");
                            } else {
                                \Log::info("ERROR: file() returned null for answers.$index.image");
                            }
                        }
                        \Log::info("Creating answer with image=" . ($imagePath ?? 'NULL'));
                        $question->answers()->create([
                            'answer_text' => $hasText ? $answer['text'] : null,
                            'image' => $imagePath,
                            'is_correct' => $isCorrect ? 1 : 0,
                            'sort_order' => $answer['sort_order'] ?? $index,
                        ]);
                        \Log::info("Created answer: text=" . ($hasText ? $answer['text'] : 'NULL') . ", image=" . ($imagePath ?? 'NULL') . ", is_correct=" . ($isCorrect ? '1' : '0'));
                    }
                }
            }
            
            // Пары для matching
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
            
            // Элементы для ordering
            if ($validated['type'] === 'ordering' && !empty($validated['ordering_items'])) {
                foreach ($validated['ordering_items'] as $item) {
                    if (!empty($item['item_text'])) {
                        $question->orderingItems()->create([
                            'item_text' => $item['item_text'],
                            'correct_order' => $item['correct_order'] ?? 0,
                            'sort_order' => $item['sort_order'] ?? 0,
                        ]);
                    }
                }
            }
            
            \Log::info('=== END QUESTION STORE DEBUG ===');
        });
        
        return redirect()->route('admin.tests.questions.index', $test)
            ->with('success', 'Вопрос успешно создан.');
    }

    public function show(Test $test, Question $question)
    {
        Gate::authorize('view', $question);
        $question->load(['answers', 'matchingPairs', 'orderingItems']);
        return view('admin.questions.show', compact('test', 'question'));
    }

    public function edit(Test $test, Question $question)
    {
        Gate::authorize('update', $question);
        $question->load(['answers', 'matchingPairs', 'orderingItems']);
        return view('admin.questions.edit', compact('test', 'question'));
    }

    public function update(Request $request, Test $test, Question $question)
    {
        Gate::authorize('update', $question);
        
        $validated = $request->validate([
            'type' => 'required|in:multiple_choice,matching,ordering',
            'question_text' => 'required|string',
            'explanation' => 'nullable|string',
            'score' => 'nullable|integer|min:1',
            'sort_order' => 'nullable|integer',
            'is_required' => 'boolean',
            'answers' => 'nullable|array',
            'answers.*.text' => 'nullable|string',
            'matching_pairs' => 'nullable|array',
            'ordering_items' => 'nullable|array',
        ]);
        
        // Валидация картинок вручную
        foreach ($request->input('answers', []) as $index => $answer) {
            if ($request->hasFile("answers.$index.image")) {
                $request->validate([
                    "answers.$index.image" => 'image|max:2048',
                ]);
            }
        }
        
        DB::transaction(function () use ($question, $validated, $request) {
            $question->update([
                'type' => $validated['type'],
                'question_text' => $validated['question_text'],
                'explanation' => $validated['explanation'] ?? null,
                'score' => $validated['score'] ?? 1,
                'sort_order' => $validated['sort_order'] ?? $question->sort_order,
                'is_required' => $validated['is_required'] ?? true,
            ]);
            
            // Ответы
            if ($validated['type'] === 'multiple_choice') {
                $question->answers()->delete();
                foreach ($request->input('answers', []) as $index => $answer) {
                    $hasText = !empty($answer['text']);
                    $hasImage = $request->hasFile("answers.$index.image");
                    $isCorrect = isset($answer['is_correct']) && $answer['is_correct'] === '1';
                    
                    if ($hasText || $hasImage) {
                        $imagePath = null;
                        if ($hasImage) {
                            $imagePath = $request->file("answers.$index.image")->store('questions/answers', 'public');
                        }
                        $question->answers()->create([
                            'answer_text' => $hasText ? $answer['text'] : null,
                            'image' => $imagePath,
                            'is_correct' => $isCorrect ? 1 : 0,
                            'sort_order' => $answer['sort_order'] ?? $index,
                        ]);
                    }
                }
            }
            
            // Пары
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
            
            // Элементы ordering
            if ($validated['type'] === 'ordering' && !empty($validated['ordering_items'])) {
                $question->orderingItems()->delete();
                foreach ($validated['ordering_items'] as $item) {
                    if (!empty($item['item_text'])) {
                        $question->orderingItems()->create([
                            'item_text' => $item['item_text'],
                            'correct_order' => $item['correct_order'] ?? 0,
                            'sort_order' => $item['sort_order'] ?? 0,
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
