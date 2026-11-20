import { Head, Link, router } from '@inertiajs/react';
import type {
    Paginated,
    QuestionFilters,
    QuestionRow,
    SubjectOption,
    TagOption,
} from '@/types/question';
import { useState } from 'react';

interface Props {
    questions: Paginated<QuestionRow>;
    filters: QuestionFilters;
    subjects: SubjectOption[];
    tags: TagOption[];
}

const field = 'rounded-md border border-input bg-background px-3 py-2 text-sm';

export default function QuestionsIndex({
    questions,
    filters,
    subjects,
    tags,
}: Props) {
    const [q, setQ] = useState(filters.q ?? '');

    function apply(next: QuestionFilters) {
        const query = Object.fromEntries(
            Object.entries(next).filter(
                ([, value]) => value !== undefined && value !== '',
            ),
        );
        router.get('/questions', query, {
            preserveState: true,
            replace: true,
        });
    }

    const update = (patch: QuestionFilters) =>
        apply({
            ...filters,
            q,
            ...patch,
        });

    const topics = filters.subject_id
        ? (subjects.find((s) => String(s.id) === filters.subject_id)?.topics ??
          [])
        : subjects.flatMap((s) => s.topics);

    function post(url: string) {
        router.post(
            url,
            {},
            {
                preserveScroll: true,
            },
        );
    }

    function remove(question: QuestionRow) {
        if (confirm('Delete this draft question? This cannot be undone.')) {
            router.delete(`/questions/${question.id}`, {
                preserveScroll: true,
            });
        }
    }

    return (
        <>
            <Head title="Question bank" />
            <div className="space-y-4 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold">
                        Question bank ({questions.total})
                    </h1>
                    <Link
                        href="/questions/create"
                        className="rounded-md bg-primary px-3 py-2 text-sm text-primary-foreground"
                    >
                        New question
                    </Link>
                </div>

                <form
                    className="flex flex-wrap gap-2"
                    onSubmit={(e) => {
                        e.preventDefault();
                        update({});
                    }}
                >
                    <input
                        className={field}
                        placeholder="Search text..."
                        value={q}
                        onChange={(e) => setQ(e.target.value)}
                    />
                    <select
                        className={field}
                        value={filters.subject_id ?? ''}
                        onChange={(e) =>
                            update({ subject_id: e.target.value, topic_id: '' })
                        }
                    >
                        <option value="">All subjects</option>
                        {subjects.map((s) => (
                            <option key={s.id} value={s.id}>
                                {s.name}
                            </option>
                        ))}
                    </select>
                    <select
                        className={field}
                        value={filters.topic_id ?? ''}
                        onChange={(e) => update({ topic_id: e.target.value })}
                    >
                        <option value="">All topics</option>
                        {topics.map((t) => (
                            <option key={t.id} value={t.id}>
                                {t.name}
                            </option>
                        ))}
                    </select>
                    <select
                        className={field}
                        value={filters.tag ?? ''}
                        onChange={(e) => update({ tag: e.target.value })}
                    >
                        <option value="">All tags</option>
                        {tags.map((t) => (
                            <option key={t.id} value={t.slug}>
                                {t.name}
                            </option>
                        ))}
                    </select>
                    <select
                        className={field}
                        value={filters.difficulty ?? ''}
                        onChange={(e) => update({ difficulty: e.target.value })}
                    >
                        <option value="">Any difficulty</option>
                        <option value="easy">Easy</option>
                        <option value="medium">Medium</option>
                        <option value="hard">Hard</option>
                    </select>
                    <select
                        className={field}
                        value={filters.status ?? ''}
                        onChange={(e) => update({ status: e.target.value })}
                    >
                        <option value="">Any status</option>
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
                    </select>
                    <button type="submit" className={field}>
                        Search
                    </button>
                </form>

                <table className="w-full text-left text-sm">
                    <thead>
                        <tr className="border-b">
                            <th className="py-2">Question</th>
                            <th>Subject / topic</th>
                            <th>Tags</th>
                            <th>Difficulty</th>
                            <th>Status</th>
                            <th />
                        </tr>
                    </thead>
                    <tbody>
                        {questions.data.map((question) => (
                            <tr
                                key={question.id}
                                className="border-b align-top"
                            >
                                <td className="max-w-md py-2 pr-2">
                                    <Link
                                        href={`/questions/${question.id}/edit`}
                                        className="hover:underline"
                                    >
                                        {question.body.length > 120
                                            ? `${question.body.slice(0, 120)}...`
                                            : question.body}
                                    </Link>
                                </td>
                                <td>
                                    {question.topic.subject.name} /{' '}
                                    {question.topic.name}
                                </td>
                                <td>
                                    {question.tags
                                        .map((t) => t.name)
                                        .join(', ')}
                                </td>
                                <td className="capitalize">
                                    {question.difficulty}
                                </td>
                                <td className="capitalize">
                                    {question.status}
                                </td>
                                <td className="space-x-2 text-right whitespace-nowrap">
                                    {question.status === 'draft' ? (
                                        <>
                                            <button
                                                onClick={() =>
                                                    post(
                                                        `/questions/${question.id}/publish`,
                                                    )
                                                }
                                            >
                                                Publish
                                            </button>
                                            <button
                                                onClick={() => remove(question)}
                                            >
                                                Delete
                                            </button>
                                        </>
                                    ) : (
                                        <button
                                            onClick={() =>
                                                post(
                                                    `/questions/${question.id}/unpublish`,
                                                )
                                            }
                                        >
                                            Unpublish
                                        </button>
                                    )}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>

                <nav className="flex flex-wrap gap-1">
                    {questions.links.map((link, i) => (
                        <Link
                            key={i}
                            href={link.url ?? '#'}
                            preserveScroll
                            className={`rounded border px-3 py-1 text-sm ${
                                link.active
                                    ? 'bg-primary text-primary-foreground'
                                    : ''
                            } ${
                                link.url ? '' : 'pointer-events-none opacity-50'
                            } `}
                            // Labels come from Laravel's paginator (e.g. "&laquo; Previous"), not user input.
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ))}
                </nav>
            </div>
        </>
    );
}

QuestionsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Question bank',
            href: '/questions',
        },
    ],
};
