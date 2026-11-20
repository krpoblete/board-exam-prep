<?php

namespace App\Http\Controllers;

use App\Actions\SaveQuestion;
use App\Enums\QuestionStatus;
use App\Http\Requests\StoreQuestionRequest;
use App\Http\Requests\UpdateQuestionRequest;
use App\Models\Choice;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class QuestionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Question::class);

        $questions = Question::query()
            ->with(['topic.subject', 'tags'])
            ->withCount('choices')
            ->when($request->string('q')->toString(), fn ($q, $term) => $q->where('body', 'ilike', "%{$term}%"))
            ->when($request->integer('topic_id'), fn ($q, $id) => $q->where('topic_id', $id))
            ->when($request->integer('subject_id'), fn ($q, $id) => $q->whereHas('topic', fn ($t) => $t->where('subject_id', $id)))
            ->when($request->string('tag')->toString(), fn ($q, $slug) => $q->whereHas('tags', fn ($t) => $t->where('slug', $slug)))
            ->when($request->string('status')->toString(), fn ($q, $s) => $q->where('status', $s))
            ->when($request->string('difficulty')->toString(), fn ($q, $d) => $q->where('difficulty', $d))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('questions/index', [
            'questions' => $questions,
            'filters' => $request->only([
                'q',
                'subject_id',
                'topic_id',
                'tag',
                'status',
                'difficulty',
            ]),
            'subjects' => Subject::with('topics:id,subject_id,name')->get(),
            'tags' => Tag::orderBy('name')->get([
                'id',
                'name',
                'slug',
            ]),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        Gate::authorize('create', Question::class);

        return Inertia::render('questions/form', [
            'question' => null,
            ...$this->formOptions(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreQuestionRequest $request, SaveQuestion $save): RedirectResponse
    {
        $question = $save->handle($request->validated(), $request->user());

        return to_route('questions.edit', $question);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Question $question): Response
    {
        Gate::authorize('update', $question);

        $question->load([
            'topic',
            'choices',
            'tags',
        ]);

        return Inertia::render('questions/form', [
            'question' => [
                'id' => $question->id,
                'subject_id' => $question
                    ->topic
                    ->subject_id,
                'topic_id' => $question
                    ->topic_id,
                'body' => $question
                    ->body,
                'explanation' => $question
                    ->explanation,
                'difficulty' => $question
                    ->difficulty
                    ->value,
                'status' => $question
                    ->status
                    ->value,
                'locked' => $question
                    ->hasAttempts(),
                'tags' => $question
                    ->tags
                    ->pluck('name')
                    ->values(),
                'choices' => $question
                    ->choices
                    ->map(fn (Choice $c): array => [
                        'id' => $c->id,
                        'body' => $c->body,
                        'is_correct' => $c->is_correct,
                    ])
                    ->values(),
            ],
            ...$this->formOptions(),
        ]);
    }

    /** @return array<string, mixed> */
    private function formOptions(): array
    {
        return [
            'subjects' => Subject::with('topics:id,subject_id,name')
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                ]),
            'tagNames' => Tag::orderBy('name')
                ->pluck('name'),
        ];
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateQuestionRequest $request, Question $question, SaveQuestion $save): RedirectResponse
    {
        $save->handle($request->validated(), $request->user(), $question);

        return back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Question $question): RedirectResponse
    {
        Gate::authorize('delete', $question);
        $question->delete();

        return to_route('questions.index');
    }

    public function publish(Question $question): RedirectResponse
    {
        Gate::authorize('publish', $question);

        $choices = $question->choices()->get();

        if ($choices->count() < 2 || $choices->where('is_correct', true)->count() !== 1) {
            throw ValidationException::withMessages([
                'status' => 'A question needs at least two choices and exactly one correct choice to be published.',
            ]);
        }

        $question->update(['status' => QuestionStatus::Published]);

        return back();
    }

    public function unpublish(Question $question): RedirectResponse
    {
        Gate::authorize('unpublish', $question);
        $question->update(['status' => QuestionStatus::Draft]);

        return back();
    }
}
