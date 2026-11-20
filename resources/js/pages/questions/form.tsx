import { Head, Link, router, useForm } from '@inertiajs/react';
import type {
    ChoiceInput,
    QuestionDetail,
    SubjectOption,
} from '@/types/question';
import React, { useState } from 'react';

interface Props {
    question: QuestionDetail | null;
    subjects: SubjectOption[];
    tagNames: string[];
}

const field =
    'w-full rounded-md border border-input bg-background px-3 py-2 text-sm';
const blankChoices: ChoiceInput[] = [
    {
        body: '',
        is_correct: true,
    },
    {
        body: '',
        is_correct: false,
    },
];

export default function QuestionForm({ question, subjects, tagNames }: Props) {
    const isEdit = question !== null;
    const locked = question?.locked ?? false;

    const [subjectId, setSubjectId] = useState(
        question ? String(question.subject_id) : '',
    );
    const [tagText, setTagText] = useState(question?.tags.join(', ') ?? '');
    const [statusError, setStatusError] = useState<string | null>(null);

    const form = useForm({
        topic_id: question ? String(question.topic_id) : '',
        body: question?.body ?? '',
        explanation: question?.explanation ?? '',
        difficulty: question?.difficulty ?? 'medium',
        choices: question?.choices ?? blankChoices,
        tags: [] as string[],
    });

    const errors = form.errors as Record<string, string>;
    const topics =
        subjects.find((s) => String(s.id) === subjectId)?.topics ?? [];

    const setChoices = (next: ChoiceInput[]) => form.setData('choices', next);
    const editChoice = (i: number, patch: Partial<ChoiceInput>) =>
        setChoices(
            form.data.choices.map((c, idx) =>
                idx === i ? { ...c, ...patch } : c,
            ),
        );
    const markCorrect = (i: number) =>
        setChoices(
            form.data.choices.map((c, idx) => ({
                ...c,
                is_correct: idx === i,
            })),
        );
    const removeChoice = (i: number) => {
        const next = form.data.choices.filter((_, idx) => idx !== i);
        // Keep exactly one correct choice if the correct one was removed.
        if (next.length > 0 && !next.some((c) => c.is_correct))
            next[0] = { ...next[0], is_correct: true };
        setChoices(next);
    };

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.transform((data) => ({
            ...data,
            tags: tagText
                .split(',')
                .map((t) => t.trim())
                .filter(Boolean),
        }));

        if (isEdit) {
            form.put(`/questions/${question.id}`, {
                preserveScroll: true,
            });
        } else {
            form.post('/questions');
        }
    }

    function changeStatus(action: 'publish' | 'unpublish') {
        setStatusError(null);
        router.post(
            `/questions/${question?.id}/${action}`,
            {},
            {
                preserveScroll: true,
                onError: (errs) =>
                    setStatusError(errs.status ?? 'Could not change status.'),
            },
        );
    }

    return (
        <>
            <Head title={isEdit ? 'Edit question' : 'New question'} />
            <form onSubmit={submit} className="max-w-3xl space-y-5 p-4">
                {isEdit && (
                    <div className="flex items-center justify-between rounded-md border p-3 text-sm">
                        <span>
                            Status:{' '}
                            <strong className="capitalize">
                                {question.status}
                            </strong>
                        </span>
                        {question.status === 'draft' ? (
                            <button
                                type="button"
                                onClick={() => changeStatus('publish')}
                            >
                                Publish
                            </button>
                        ) : (
                            <button
                                type="button"
                                onClick={() => changeStatus('unpublish')}
                            >
                                Unpublish
                            </button>
                        )}
                    </div>
                )}
                {statusError && (
                    <p className="text-sm text-red-600">{statusError}</p>
                )}
                {locked && (
                    <p className="rounded-md border p-3 text-sm">
                        This question already has attempts. You can fix wording,
                        but you cannot add, remove choices, or change the
                        correct answer.
                    </p>
                )}

                <div className="grid grid-cols-2 gap-3">
                    <label className="space-y-1 text-sm">
                        Subject
                        <select
                            className={field}
                            value={subjectId}
                            onChange={(e) => {
                                setSubjectId(e.target.value);
                                form.setData('topic_id', '');
                            }}
                        >
                            <option value="">Select subject</option>
                            {subjects.map((s) => (
                                <option key={s.id} value={s.id}>
                                    {s.name}
                                </option>
                            ))}
                        </select>
                    </label>
                    <label className="space-y-1 text-sm">
                        Topic
                        <select
                            className={field}
                            value={form.data.topic_id}
                            onChange={(e) =>
                                form.setData('topic_id', e.target.value)
                            }
                            disabled={!subjectId}
                        >
                            <option value="">Select topic</option>
                            {topics.map((t) => (
                                <option key={t.id} value={t.id}>
                                    {t.name}
                                </option>
                            ))}
                            {errors.topic_id && (
                                <span className="text-red-600">
                                    {errors.topic_id}
                                </span>
                            )}
                        </select>
                    </label>
                </div>

                <label className="block space-y-1 text-sm">
                    Question
                    <textarea
                        className={field}
                        rows={4}
                        value={form.data.body}
                        onChange={(e) => form.setData('body', e.target.value)}
                    />
                    {errors.body && (
                        <span className="text-red-600">{errors.body}</span>
                    )}
                </label>

                <fieldset className="space-y-2">
                    <legend className="text-sm">
                        Choices (select the correct one)
                    </legend>
                    {form.data.choices.map((choice, i) => (
                        <div
                            key={choice.id ?? `new-${i}`}
                            className="space-y-1"
                        >
                            <div className="flex items-center gap-2">
                                <input
                                    type="radio"
                                    name="correct"
                                    checked={choice.is_correct}
                                    disabled={locked}
                                    onChange={() => markCorrect(i)}
                                />
                                <input
                                    className={field}
                                    value={choice.body}
                                    onChange={(e) =>
                                        editChoice(i, { body: e.target.value })
                                    }
                                />
                                <button
                                    type="button"
                                    disabled={
                                        locked || form.data.choices.length <= 2
                                    }
                                    onClick={() => removeChoice(i)}
                                >
                                    Remove
                                </button>
                            </div>
                            {errors[`choices.${i}.body`] && (
                                <p className="text-sm text-red-600">
                                    {errors[`choices.${i}.body`]}
                                </p>
                            )}
                        </div>
                    ))}
                    {errors.choices && (
                        <p className="text-sm text-red-600">{errors.choices}</p>
                    )}
                    <button
                        type="button"
                        disabled={locked || form.data.choices.length >= 6}
                        onClick={() =>
                            setChoices([
                                ...form.data.choices,
                                { body: '', is_correct: false },
                            ])
                        }
                    >
                        Add choice
                    </button>
                </fieldset>

                <label className="block space-y-1 text-sm">
                    Explanation (optional)
                    <textarea
                        className={field}
                        rows={3}
                        value={form.data.explanation}
                        onChange={(e) =>
                            form.setData('explanation', e.target.value)
                        }
                    />
                </label>

                <div className="grid grid-cols-2 gap-3">
                    <label className="space-y-1 text-sm">
                        Difficulty
                        <select
                            className={field}
                            value={form.data.difficulty}
                            onChange={(e) =>
                                form.setData(
                                    'difficulty',
                                    e.target
                                        .value as typeof form.data.difficulty,
                                )
                            }
                        >
                            <option value="easy">Easy</option>
                            <option value="medium">Medium</option>
                            <option value="hard">Hard</option>
                        </select>
                    </label>
                    <label className="space-y-1 text-sm">
                        Tags (comma-separated)
                        <input
                            className={field}
                            list="tag-options"
                            value={tagText}
                            onChange={(e) => setTagText(e.target.value)}
                        />
                        <datalist id="tag-options">
                            {tagNames.map((t) => (
                                <option key={t} value={t} />
                            ))}
                        </datalist>
                    </label>
                </div>

                <div className="flex gap-2">
                    <button
                        type="submit"
                        disabled={form.processing}
                        className="rounded-md bg-primary px-4 py-2 text-sm text-primary-foreground"
                    >
                        {isEdit ? 'Save changes' : 'Create question'}
                    </button>
                    <Link
                        href="/questions"
                        className="rounded-md border px-4 py-2 text-sm"
                    >
                        Cancel
                    </Link>
                </div>
            </form>
        </>
    );
}

QuestionForm.layout = {
    breadcrumbs: [
        {
            title: 'Question bank',
            href: '/questions',
        },
        {
            title: 'Question',
            href: '/questions',
        },
    ],
};
