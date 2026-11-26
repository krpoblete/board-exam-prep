import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import type {
    Paginated,
    QuestionFilters,
    QuestionRow,
    SubjectOption,
    TagOption,
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
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import importRoutes from '@/routes/questions/import';
import questionRoutes from '@/routes/questions';

interface Props {
    questions: Paginated<QuestionRow>;
    filters: QuestionFilters;
    subjects: SubjectOption[];
    tags: TagOption[];
    bulk: {
        published: number;
        skipped: number;
    } | null;
}

const ALL = 'all';

function FilterSelect({
    value,
    onChange,
    placeholder,
    options,
}: {
    value: string | undefined;
    onChange: (value: string) => void;
    placeholder: string;
    options: {
        value: string;
        label: string;
    }[];
}) {
    return (
        <Select
            value={value || ALL}
            onValueChange={(v) => onChange(v === ALL ? '' : v)}
        >
            <SelectTrigger className="w-44">
                <SelectValue placeholder={placeholder} />
            </SelectTrigger>
            <SelectContent>
                <SelectItem value={ALL}>{placeholder}</SelectItem>
                {options.map((option) => (
                    <SelectItem key={option.value} value={option.value}>
                        {option.label}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}

export default function QuestionsIndex({
    questions,
    filters,
    subjects,
    tags,
    bulk,
}: Props) {
    const [q, setQ] = useState(filters.q ?? '');
    const [selected, setSelected] = useState<number[]>([]);

    const pageIds = questions.data.map((question) => question.id);
    // Ignore ids from a previous page or filter, so the count is always what's visible.
    const selectedOnPage = selected.filter((id) => pageIds.includes(id));
    const allSelected =
        pageIds.length > 0 && selectedOnPage.length === pageIds.length;

    function apply(next: QuestionFilters) {
        const query = Object.fromEntries(
            Object.entries(next).filter(
                ([, value]) => value !== undefined && value !== '',
            ),
        );
        router.get(questionRoutes.index().url, query, {
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

    function toggle(id: number) {
        setSelected((prev) =>
            prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id],
        );
    }

    function toggleAll() {
        setSelected(allSelected ? [] : pageIds);
    }

    function changeStatus(
        question: QuestionRow,
        action: 'publish' | 'unpublish',
    ) {
        const route =
            action === 'publish'
                ? questionRoutes.publish
                : questionRoutes.unpublish;

        router.post(
            route(question.id).url,
            {},
            {
                preserveScroll: true,
            },
        );
    }

    function remove(question: QuestionRow) {
        if (confirm('Delete this draft question? This cannot be undone.')) {
            router.delete(questionRoutes.destroy(question.id).url, {
                preserveScroll: true,
            });
        }
    }

    function publishSelected() {
        router.post(
            questionRoutes.bulkPublish().url,
            { ids: selectedOnPage },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setSelected([]);
                    router.flushAll();
                },
            },
        );
    }

    function publishAllMatching() {
        const message = `Publish all ${questions.total} draft questions matching these filters? Questions without two choices and one correct answer are skipped.`;

        if (confirm(message)) {
            router.post(
                questionRoutes.bulkPublish().url,
                { all_matching: true, ...filters },
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        setSelected([]);
                        router.flushAll();
                    },
                },
            );
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
                    <Button asChild variant="outline">
                        <Link href={importRoutes.create()}>Import CSV</Link>
                    </Button>
                    <Button asChild>
                        <Link href={questionRoutes.create()}>New question</Link>
                    </Button>
                </div>

                <form
                    className="flex flex-wrap gap-2"
                    onSubmit={(e) => {
                        e.preventDefault();
                        update({});
                    }}
                >
                    <Input
                        className="w-56"
                        placeholder="Search text..."
                        value={q}
                        onChange={(e) => setQ(e.target.value)}
                    />
                    <FilterSelect
                        value={filters.subject_id}
                        placeholder="All subjects"
                        options={subjects.map((s) => ({
                            value: String(s.id),
                            label: s.name,
                        }))}
                        onChange={(v) =>
                            update({ subject_id: v, topic_id: '' })
                        }
                    />
                    <FilterSelect
                        value={filters.topic_id}
                        placeholder="All topics"
                        options={topics.map((t) => ({
                            value: String(t.id),
                            label: t.name,
                        }))}
                        onChange={(v) => update({ topic_id: v })}
                    />
                    <FilterSelect
                        value={filters.tag}
                        placeholder="All tags"
                        options={tags.map((t) => ({
                            value: t.slug,
                            label: t.name,
                        }))}
                        onChange={(v) => update({ tag: v })}
                    />
                    <FilterSelect
                        value={filters.difficulty}
                        placeholder="Any difficulty"
                        options={[
                            {
                                value: 'easy',
                                label: 'Easy',
                            },
                            {
                                value: 'medium',
                                label: 'Medium',
                            },
                            {
                                value: 'hard',
                                label: 'Hard',
                            },
                        ]}
                        onChange={(v) => update({ difficulty: v })}
                    />
                    <FilterSelect
                        value={filters.status}
                        placeholder="Any status"
                        options={[
                            {
                                value: 'draft',
                                label: 'Draft',
                            },
                            {
                                value: 'published',
                                label: 'Published',
                            },
                        ]}
                        onChange={(v) => update({ status: v })}
                    />
                    <Button type="submit" variant="secondary">
                        Search
                    </Button>
                </form>

                {bulk && (
                    <div className="rounded-md border p-3 text-sm">
                        Published {bulk.published} question(s).
                        {bulk.skipped > 0 &&
                            ` Skipped ${bulk.skipped} without two choices and exactly one correct answer.`}
                    </div>
                )}

                {(selectedOnPage.length > 0 ||
                    (filters.status === 'draft' && questions.total > 0)) && (
                    <div className="flex flex-wrap items-center gap-2 rounded-md border p-3 text-sm">
                        {selectedOnPage.length > 0 && (
                            <>
                                <span>{selectedOnPage.length} selected</span>
                                <Button size="sm" onClick={publishSelected}>
                                    Publish selected
                                </Button>
                            </>
                        )}
                        {filters.status === 'draft' && questions.total > 0 && (
                            <Button
                                size="sm"
                                variant="outline"
                                onClick={publishAllMatching}
                            >
                                Publish all {questions.total} drafts
                            </Button>
                        )}
                    </div>
                )}

                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead className="w-8">
                                <input
                                    type="checkbox"
                                    aria-label="Select all on this page"
                                    className="size-4 accent-primary"
                                    checked={allSelected}
                                    onChange={toggleAll}
                                />
                            </TableHead>
                            <TableHead>Question</TableHead>
                            <TableHead>Subject / topic</TableHead>
                            <TableHead>Tags</TableHead>
                            <TableHead>Difficulty</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead />
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {questions.data.map((question) => (
                            <TableRow key={question.id} className="align-top">
                                <TableCell>
                                    <input
                                        type="checkbox"
                                        aria-label={`Select question ${question.id}`}
                                        className="size-4 accent-primary"
                                        checked={selected.includes(question.id)}
                                        onChange={() => toggle(question.id)}
                                    />
                                </TableCell>
                                <TableCell className="max-w-md whitespace-normal">
                                    <Link
                                        href={questionRoutes.edit(question.id)}
                                        className="hover:underline"
                                    >
                                        {question.body.length > 120
                                            ? `${question.body.slice(0, 120)}...`
                                            : question.body}
                                    </Link>
                                </TableCell>
                                <TableCell>
                                    {question.topic.subject.name} /{' '}
                                    {question.topic.name}
                                </TableCell>
                                <TableCell>
                                    <div className="flex flex-wrap gap-1">
                                        {question.tags.map((tag) => (
                                            <Badge
                                                key={tag.id}
                                                variant="outline"
                                            >
                                                {tag.name}
                                            </Badge>
                                        ))}
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge
                                        variant="outline"
                                        className="capitalize"
                                    >
                                        {question.difficulty}
                                    </Badge>
                                </TableCell>
                                <TableCell>
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
                                </TableCell>
                                <TableCell className="space-x-1 text-right whitespace-nowrap">
                                    {question.status === 'draft' ? (
                                        <>
                                            <Button
                                                size="sm"
                                                variant="ghost"
                                                onClick={() =>
                                                    changeStatus(
                                                        question,
                                                        'publish',
                                                    )
                                                }
                                            >
                                                Publish
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="ghost"
                                                className="text-destructive"
                                                onClick={() => remove(question)}
                                            >
                                                Delete
                                            </Button>
                                        </>
                                    ) : (
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            onClick={() =>
                                                changeStatus(
                                                    question,
                                                    'unpublish',
                                                )
                                            }
                                        >
                                            Unpublish
                                        </Button>
                                    )}
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>

                <nav className="flex flex-wrap gap-1">
                    {questions.links.map((link, i) =>
                        link.url ? (
                            <Button
                                key={i}
                                asChild
                                size="sm"
                                variant={link.active ? 'default' : 'outline'}
                            >
                                <Link
                                    href={link.url}
                                    preserveScroll
                                    // Labels come from Laravel's paginator (e.g., "&laquo; Previous"), not user input.
                                    dangerouslySetInnerHTML={{
                                        __html: link.label,
                                    }}
                                />
                            </Button>
                        ) : (
                            <Button
                                key={i}
                                size="sm"
                                variant="outline"
                                disabled
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ),
                    )}
                </nav>
            </div>
        </>
    );
}

QuestionsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Question bank',
            href: questionRoutes.index(),
        },
    ],
};
