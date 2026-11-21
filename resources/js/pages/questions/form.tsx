import { Head, Link, router, useForm } from '@inertiajs/react';
import React, { useState } from 'react';
import type {
    ChoiceInput,
    Difficulty,
    QuestionDetail,
    SubjectOption,
} from '@/types/question';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import questionRoutes from '@/routes/questions';

interface Props {
    question: QuestionDetail | null;
    subjects: SubjectOption[];
    tagNames: string[];
}

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
        difficulty: (question?.difficulty ?? 'medium') as Difficulty,
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
        if (next.length > 0 && !next.some((c) => c.is_correct)) {
            next[0] = { ...next[0], is_correct: true };
        }

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
            form.put(questionRoutes.update(question.id).url, {
                preserveScroll: true,
            });
        } else {
            form.post(questionRoutes.store().url);
        }
    }

    function changeStatus(action: 'publish' | 'unpublish') {
        if (!question) {
            return;
        }

        const route =
            action === 'publish'
                ? questionRoutes.publish
                : questionRoutes.unpublish;

        setStatusError(null);
        router.post(
            route(question.id).url,
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
                        <span className="flex items-center gap-2">
                            Status:
                            <Badge
                                variant={
                                    question.status === 'published'
                                        ? 'default'
                                        : 'secondary'
                                }
                                className="capitalize"
                            >
                                {question.status}
                            </Badge>
                        </span>
                        {question.status === 'draft' ? (
                            <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                onClick={() => changeStatus('publish')}
                            >
                                Publish
                            </Button>
                        ) : (
                            <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                onClick={() => changeStatus('unpublish')}
                            >
                                Unpublish
                            </Button>
                        )}
                    </div>
                )}
                {statusError && (
                    <p className="text-sm text-destructive">{statusError}</p>
                )}
                {locked && (
                    <p className="rounded-md border p-3 text-sm">
                        This question already has attempts. You can fix wording,
                        but you cannot add, remove choices, or change the
                        correct answer.
                    </p>
                )}

                <div className="grid grid-cols-2 gap-3">
                    <div className="space-y-1">
                        <label htmlFor="subject" className="text-sm">
                            Subject
                        </label>
                        <Select
                            value={subjectId}
                            onValueChange={(value) => {
                                setSubjectId(value);
                                form.setData('topic_id', '');
                            }}
                        >
                            <SelectTrigger id="subject" className="w-full">
                                <SelectValue placeholder="Select subject" />
                            </SelectTrigger>
                            <SelectContent>
                                {subjects.map((s) => (
                                    <SelectItem key={s.id} value={String(s.id)}>
                                        {s.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="space-y-1">
                        <label htmlFor="topic" className="text-sm">
                            Topic
                        </label>
                        <Select
                            value={form.data.topic_id}
                            onValueChange={(value) =>
                                form.setData('topic_id', value)
                            }
                            disabled={!subjectId}
                        >
                            <SelectTrigger id="topic" className="w-full">
                                <SelectValue placeholder="Select topic" />
                            </SelectTrigger>
                            <SelectContent>
                                {topics.map((t) => (
                                    <SelectItem key={t.id} value={String(t.id)}>
                                        {t.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.topic_id && (
                            <p className="text-sm text-destructive">
                                {errors.topic_id}
                            </p>
                        )}
                    </div>
                </div>

                <div className="space-y-1">
                    <label htmlFor="body" className="text-sm">
                        Question
                    </label>
                    <Textarea
                        id="body"
                        rows={4}
                        value={form.data.body}
                        onChange={(e) => form.setData('body', e.target.value)}
                        disabled={!subjectId}
                    />
                    {errors.body && (
                        <p className="text-sm text-destructive">
                            {errors.body}
                        </p>
                    )}
                </div>

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
                                    aria-label={`Mark choice ${i + 1} as correct`}
                                    className="size-4 accent-primary"
                                    checked={choice.is_correct}
                                    disabled={locked}
                                    onChange={() => markCorrect(i)}
                                />
                                <Input
                                    value={choice.body}
                                    onChange={(e) =>
                                        editChoice(i, { body: e.target.value })
                                    }
                                />
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="ghost"
                                    disabled={
                                        locked || form.data.choices.length <= 2
                                    }
                                    onClick={() => removeChoice(i)}
                                >
                                    Remove
                                </Button>
                            </div>
                            {errors[`choices.${i}.body`] && (
                                <p className="text-sm text-destructive">
                                    {errors[`choices.${i}.body`]}
                                </p>
                            )}
                        </div>
                    ))}
                    {errors.choices && (
                        <p className="text-sm text-destructive">
                            {errors.choices}
                        </p>
                    )}
                    <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        disabled={locked || form.data.choices.length >= 6}
                        onClick={() =>
                            setChoices([
                                ...form.data.choices,
                                { body: '', is_correct: false },
                            ])
                        }
                    >
                        Add choice
                    </Button>
                </fieldset>

                <div className="space-y-1">
                    <label htmlFor="explanation" className="text-sm">
                        Explanation
                    </label>
                    <Textarea
                        id="explanation"
                        rows={3}
                        value={form.data.explanation}
                        onChange={(e) =>
                            form.setData('explanation', e.target.value)
                        }
                    />
                </div>

                <div className="grid grid-cols-2 gap-3">
                    <div className="space-y-1">
                        <label htmlFor="difficulty" className="text-sm">
                            Difficulty
                        </label>
                        <Select
                            value={form.data.difficulty}
                            onValueChange={(value) =>
                                form.setData('difficulty', value as Difficulty)
                            }
                        >
                            <SelectTrigger id="difficulty" className="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="easy">Easy</SelectItem>
                                <SelectItem value="medium">Medium</SelectItem>
                                <SelectItem value="hard">Hard</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="space-y-1">
                        <label htmlFor="tags" className="text-sm">
                            Tags (comma-separated)
                        </label>
                        <Input
                            id="tags"
                            list="tag-options"
                            value={tagText}
                            onChange={(e) => setTagText(e.target.value)}
                        />
                        <datalist id="tag-options">
                            {tagNames.map((t) => (
                                <option key={t} value={t} />
                            ))}
                        </datalist>
                    </div>
                </div>

                <div className="flex gap-2">
                    <Button type="submit" disabled={form.processing}>
                        {isEdit ? 'Save changes' : 'Create question'}
                    </Button>
                    <Button asChild variant="outline">
                        <Link href={questionRoutes.index()}>Cancel</Link>
                    </Button>
                </div>
            </form>
        </>
    );
}

QuestionForm.layout = {
    breadcrumbs: [
        {
            title: 'Question bank',
            href: questionRoutes.index(),
        },
        {
            title: 'Question',
            href: questionRoutes.index(),
        },
    ],
};
