<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTopicRequest;
use App\Models\Subject;
use App\Models\Topic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TopicController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Topic::class);

        return Inertia::render('topics/index', [
            'subjects' => Subject::with([
                'topics' => fn ($q) => $q
                    ->withCount('questions')
                    ->orderBy('name'),
            ])->get(),
        ]);
    }

    public function store(StoreTopicRequest $request): RedirectResponse
    {
        Topic::create($request->validated());

        return back();
    }

    public function update(StoreTopicRequest $request, Topic $topic): RedirectResponse
    {
        $topic->update($request->validated());

        return back();
    }

    public function destroy(Topic $topic): RedirectResponse
    {
        Gate::authorize('delete', $topic);
        $topic->delete();

        return back();
    }
}
