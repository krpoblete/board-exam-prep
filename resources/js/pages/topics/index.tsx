import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

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

const field = 'rounded-md border border-input bg-background px-3 py-2 text-sm';

function TopicRow({ topic }: { topic: TopicRowData }) {
    const [editing, setEditing] = useState(false);
    const [name, setName] = useState(topic.name);
    const [error, setError] = useState<string | null>(null);

    function save() {
        router.put(
            `/topics/${topic.id}`,
            { name },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setEditing(false);
                    setError(null);
                },
                onError: (errs) => setError(errs.name ?? 'Could not save.'),
            },
        );
    }

    return (
        <li className="flex items-center gap-2 py-1">
            {editing ? (
                <>
                    <input
                        className={field}
                        value={name}
                        onChange={(e) => setName(e.target.value)}
                    />
                    <button onClick={save}>Save</button>
                    <button
                        onClick={() => {
                            setEditing(false);
                            setName(topic.name);
                            setError(null);
                        }}
                    >
                        Cancel
                    </button>
                    {error && (
                        <span className="text-sm text-red-600">{error}</span>
                    )}
                </>
            ) : (
                <>
                    <span className="flex-1">
                        {topic.name}{' '}
                        <span className="text-sm text-muted-foreground">
                            ({topic.questions_count})
                        </span>
                    </span>
                    <button onClick={() => setEditing(true)}>Rename</button>
                    <button
                        disabled={topic.questions_count > 0}
                        title={
                            topic.questions_count > 0
                                ? 'Has questions'
                                : undefined
                        }
                        onClick={() =>
                            confirm('Delete this topic?') &&
                            router.delete(`/topics/${topic.id}`, {
                                preserveScroll: true,
                            })
                        }
                    >
                        Delete
                    </button>
                </>
            )}
        </li>
    );
}

function AddTopic({ subjectId }: { subjectId: number }) {
    const form = useForm({ subjectId: subjectId, name: '' });

    return (
        <form
            className="mt-2 flex items-center gap-2"
            onSubmit={(e) => {
                e.preventDefault();
                form.post('/topics', {
                    preserveScroll: true,
                    onSuccess: () => form.reset('name'),
                });
            }}
        >
            <input
                className={field}
                placeholder="New topic"
                value={form.data.name}
                onChange={(e) => form.setData('name', e.target.value)}
            />
            <button type="submit" disabled={form.processing}>
                Add
            </button>
            {form.errors.name && (
                <span className="text-sm text-red-600">{form.errors.name}</span>
            )}
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
            <div className="max-w-2xl space-y-6 p-4">
                <h1 className="text-xl font-semibold">Topics</h1>
                {subjects.map((subject) => (
                    <section key={subject.id}>
                        <h2 className="font-medium">{subject.name}</h2>
                        <ul>
                            {subject.topics.map((topic) => (
                                <TopicRow key={topic.id} topic={topic} />
                            ))}
                        </ul>
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
            href: '/topics',
        },
    ],
};
