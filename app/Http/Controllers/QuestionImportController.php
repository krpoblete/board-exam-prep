<?php

namespace App\Http\Controllers;

use App\Actions\ImportQuestions;
use App\Models\Question;
use App\Models\Topic;
use App\Support\QuestionImport\ImportRow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuestionImportController extends Controller
{
    private const SESSION_KEY = 'question_import';

    public function create(Request $request, ImportQuestions $import): Response
    {
        Gate::authorize('import', Question::class);

        $staged = $this->staged($request);
        $preview = null;

        if ($staged !== null) {
            $result = $import->parse(Storage::disk('local')->path($staged['path']));

            $preview = [
                'file_name' => $staged['name'],
                'fatal' => $result->fatal,
                'total' => count($result->rows),
                'valid' => $result->validCount(),
                'invalid' => $result->invalidCount(),
                'rows' => array_map(fn (ImportRow $row): array => [
                    'row' => $row->row,
                    'subject' => $row->raw['subject'],
                    'topic' => $row->raw['topic'],
                    'body' => $row->raw['body'],
                    'choices' => count(array_filter([
                        $row->raw['choice_a'],
                        $row->raw['choice_b'],
                        $row->raw['choice_c'],
                        $row->raw['choice_d'],
                    ], fn (string $choice): bool => $choice !== '')),
                    'correct' => Str::upper($row->raw['correct']),
                    'errors' => $row->errors,
                ], $result->rows),
            ];
        }

        return Inertia::render('questions/import', [
            'preview' => $preview,
            'imported' => session('imported'),
        ]);
    }

    public function template(): StreamedResponse
    {
        Gate::authorize('import', Question::class);

        $topic = Topic::with('subject')->first();
        $subject = $topic?->subject->name ?? 'Verbal Ability';
        $topicName = $topic->name ?? 'Analogies';

        return response()->streamDownload(function () use ($subject, $topicName): void {
            $out = fopen('php://output', 'w');

            if ($out === false) {
                return;
            }

            // UTF-8 BOM so Excel shows accents correctly
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ImportQuestions::COLUMNS, ',', '"', '');
            fputcsv($out, [
                $subject,
                $topicName,
                'Choose the synonym of "brisk".',
                'Brisk means quick or energetic.',
                'easy',
                'vocabulary;synonyms',
                'Quick',
                'Slow',
                'Tired',
                'Quiet',
                'A',
            ], ',', '"', '');
            fputcsv($out, [
                $subject,
                $topicName,
                'Which word is a noun?',
                '',
                'medium',
                '',
                'Run',
                'Happy',
                'Table',
                '',
                'C',
            ], ',', '"', '');
            fclose($out);
        }, 'question-import-template.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function preview(Request $request): RedirectResponse
    {
        Gate::authorize('import', Question::class);

        $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:csv,txt',
                'max:2048',
            ],
        ]);

        $this->discardStaged($request);

        $file = $request->file('file');
        assert($file instanceof UploadedFile);

        $path = $file->storeAs('import', Str::uuid().'csv', 'local');

        $request->session()->put(self::SESSION_KEY, [
            'path' => $path,
            'name' => $file->getClientOriginalName(),
        ]);

        return to_route('questions.import.create');
    }

    public function store(Request $request, ImportQuestions $import): RedirectResponse
    {
        Gate::authorize('import', Question::class);

        $staged = $this->staged($request);

        if ($staged === null) {
            throw ValidationException::withMessages(['file' => 'Upload a file first.']);
        }

        $count = $import->commit(Storage::disk('local')->path($staged['path']), $request->user());

        $this->discardStaged($request);

        return to_route('questions.import.create')->with('imported', $count);
    }

    public function discard(Request $request): RedirectResponse
    {
        Gate::authorize('import', Question::class);

        $this->discardStaged($request);

        return to_route('questions.import.create');
    }

    /** @return array{path: string, name: string}|null */
    private function staged(Request $request): ?array
    {
        $staged = $request->session()->get(self::SESSION_KEY);

        if (
            ! is_array($staged)
            || ! isset($staged['path'], $staged['name'])
            || ! Storage::disk('local')->exists($staged['path'])
        ) {
            return null;
        }

        return [
            'path' => $staged['path'],
            'name' => $staged['name'],
        ];
    }

    private function discardStaged(Request $request): void
    {
        $staged = $this->staged($request);

        if ($staged !== null) {
            Storage::disk('local')->delete($staged['path']);
        }

        $request->session()->forget(self::SESSION_KEY);
    }
}
