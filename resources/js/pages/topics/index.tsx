import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import topicRoutes from '@/routes/topics';

interface TopicRowData {
    id: number;
    name: string;
    questions_count: number;
}
interface SubjectWithTopics {
    id: number;
    name: string;
    topics: TopicRowData[];
}

function TopicRowItem({ topic }: { topic: TopicRowData }) {
    const [editing, setEditing] = useState(false);
    const [name, setName] = useState(topic.name);
    const [error, setError] = useState<string | null>(null);

    function save() {
        router.put(
            topicRoutes.update(topic.id).url,
            { name },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setEditing(false);
                    setError(null);
                    router.flushAll();
                },
                onError: (errs) =>
                    setError(
                        errs.name ??
                            Object.values(errs)[0] ??
                            'Could not save.',
                    ),
            },
        );
    }

    function cancel() {
        setEditing(false);
        setName(topic.name);
        setError(null);
    }

    function remove() {
        if (confirm('Delete this topic?')) {
            router.delete(topicRoutes.destroy(topic.id).url, {
                preserveScroll: true,
                onSuccess: () => router.flushAll(),
            });
        }
    }

    return (
        <TableRow>
            <TableCell>
                {editing ? (
                    <div className="space-y-1">
                        <Input
                            value={name}
                            onChange={(e) => setName(e.target.value)}
                        />
                        {error && (
                            <p className="text-sm text-destructive">{error}</p>
                        )}
                    </div>
                ) : (
                    topic.name
                )}
            </TableCell>
            <TableCell>
                <Badge variant="secondary">{topic.questions_count}</Badge>
            </TableCell>
            <TableCell className="space-x-1 text-right whitespace-nowrap">
                {editing ? (
                    <>
                        <Button size="sm" onClick={save}>
                            Save
                        </Button>
                        <Button size="sm" variant="ghost" onClick={cancel}>
                            Cancel
                        </Button>
                    </>
                ) : (
                    <>
                        <Button
                            size="sm"
                            variant="ghost"
                            onClick={() => setEditing(true)}
                        >
                            Rename
                        </Button>
                        <Button
                            size="sm"
                            variant="ghost"
                            className="text-destructive"
                            disabled={topic.questions_count > 0}
                            title={
                                topic.questions_count > 0
                                    ? 'Has questions'
                                    : undefined
                            }
                            onClick={remove}
                        >
                            Delete
                        </Button>
                    </>
                )}
            </TableCell>
        </TableRow>
    );
}

function AddTopic({ subjectId }: { subjectId: number }) {
    const form = useForm({ subject_id: subjectId, name: '' });

    return (
        <form
            className="mt-2 flex items-start gap-2"
            onSubmit={(e) => {
                e.preventDefault();
                form.post(topicRoutes.store().url, {
                    preserveScroll: true,
                    onSuccess: () => {
                        form.reset('name');
                        router.flushAll();
                    },
                });
            }}
        >
            <div className="space-y-1">
                <Input
                    placeholder="New topic"
                    value={form.data.name}
                    onChange={(e) => form.setData('name', e.target.value)}
                />
                {form.errors.name && form.errors.subject_id && (
                    <p className="text-sm text-destructive">
                        {form.errors.name ?? form.errors.subject_id}
                    </p>
                )}
            </div>
            <Button type="submit" disabled={form.processing}>
                Add
            </Button>
        </form>
    );
}

export default function TopicsIndex({
    subjects,
}: {
    subjects: SubjectWithTopics[];
}) {
    return (
        <>
            <Head title="Topics" />
            <div className="max-w-2xl space-y-8 p-4">
                <h1 className="text-xl font-semibold">Topics</h1>
                {subjects.map((subject) => (
                    <section key={subject.id}>
                        <h2 className="mb-2 font-medium">{subject.name}</h2>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Topic</TableHead>
                                    <TableHead>Questions</TableHead>
                                    <TableHead />
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {subject.topics.map((topic) => (
                                    <TopicRowItem
                                        key={topic.id}
                                        topic={topic}
                                    />
                                ))}
                            </TableBody>
                        </Table>
                        <AddTopic subjectId={subject.id} />
                    </section>
                ))}
            </div>
        </>
    );
}

TopicsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Topics',
            href: topicRoutes.index(),
        },
    ],
};
