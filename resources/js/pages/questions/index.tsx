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
import questionRoutes from '@/routes/questions';

interface Props {
    questions: Paginated<QuestionRow>;
    filters: QuestionFilters;
    subjects: SubjectOption[];
    tags: TagOption[];
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
}: Props) {
    const [q, setQ] = useState(filters.q ?? '');

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

    return (
        <>
            <Head title="Question bank" />
            <div className="space-y-4 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold">
                        Question bank ({questions.total})
                    </h1>
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

                <Table>
                    <TableHeader>
                        <TableRow>
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
