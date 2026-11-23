import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import type { ImportPreview } from '@/types/question';
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
import importRoutes from '@/routes/questions/import';
import questionRoutes from '@/routes/questions';

interface Props {
    preview: ImportPreview | null;
    imported: number | null;
}

export default function QuestionsImport({ preview, imported }: Props) {
    const form = useForm<{ file: File | null }>({ file: null });
    const [commitError, setCommitError] = useState<string | null>(null);
    const [importing, setImporting] = useState(false);

    function check(e: React.FormEvent) {
        e.preventDefault();
        form.post(importRoutes.preview().url, { forceFormData: true });
    }

    function commit() {
        setCommitError(null);
        router.post(
            importRoutes.store().url,
            {},
            {
                onStart: () => setImporting(true),
                onFinish: () => setImporting(false),
                onSuccess: () => router.flushAll(),
                onError: (errs) =>
                    setCommitError(errs.file ?? 'The import failed.'),
            },
        );
    }

    function discard() {
        setCommitError(null);
        router.delete(importRoutes.discard().url);
    }

    const canImport =
        preview !== null &&
        preview.fatal === null &&
        preview.total > 0 &&
        preview.invalid === 0;

    return (
        <>
            <Head title="Import questions" />
            <div className="max-w-5xl space-y-6 p-4">
                <h1 className="text-xl font-semibold">
                    Import questions from CSV
                </h1>

                {typeof imported === 'number' && (
                    <div className="flex items-center justify-between rounded-md border p-3 text-sm">
                        <span>Imported {imported} question(s) as drafts.</span>
                        <Button asChild size="sm" variant="outline">
                            <Link
                                href={`${questionRoutes.index().url}?status=draft`}
                            >
                                View drafts
                            </Link>
                        </Button>
                    </div>
                )}

                <section className="space-y-2 text-sm">
                    <p>
                        One row per question. Columns: subject, topic, body,
                        explanation, difficulty, tags (separated by{' '}
                        <code>;</code>), choice_a to choice_d, and correct (A,
                        B, C, or D). Subjects and topics must already exist.
                        Imported questions are saved as drafts.
                    </p>
                    <Button asChild variant="outline" size="sm">
                        <a href={importRoutes.template().url} download>
                            Download template
                        </a>
                    </Button>
                </section>

                <form
                    onSubmit={check}
                    className="flex flex-wrap items-start gap-2"
                >
                    <div className="space-y-1">
                        <Input
                            type="file"
                            accept=".csv,text/csv"
                            className="w-80"
                            onChange={(e) =>
                                form.setData(
                                    'file',
                                    e.target.files?.[0] ?? null,
                                )
                            }
                        />
                        {form.errors.file && (
                            <p className="text-sm text-destructive">
                                {form.errors.file}
                            </p>
                        )}
                    </div>
                    <Button
                        type="submit"
                        disabled={form.processing || form.data.file === null}
                    >
                        Check file
                    </Button>
                </form>

                {preview && (
                    <section className="space-y-3">
                        <div className="flex flex-wrap items-center gap-2 text-sm">
                            <span className="font-medium">
                                {preview.file_name}
                            </span>
                            <Badge variant="secondary">
                                {preview.total} rows
                            </Badge>
                            <Badge variant="default">
                                {preview.valid} ready
                            </Badge>
                            {preview.invalid > 0 && (
                                <Badge variant="destructive">
                                    {preview.invalid} with errors
                                </Badge>
                            )}
                        </div>

                        {preview.fatal && (
                            <p className="text-sm text-destructive">
                                {preview.fatal}
                            </p>
                        )}
                        {commitError && (
                            <p className="text-sm text-destructive">
                                {commitError}
                            </p>
                        )}
                        {preview.invalid > 0 && (
                            <p className="text-sm">
                                Fix the rows with errors in your file and upload
                                it again. Nothing is imported until every row is
                                valid.
                            </p>
                        )}

                        <div className="flex gap-2">
                            <Button
                                onClick={commit}
                                disabled={!canImport || importing}
                            >
                                Import {preview.valid} questions
                            </Button>
                            <Button variant="outline" onClick={discard}>
                                Discard
                            </Button>
                        </div>

                        {preview.rows.length > 0 && (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Row</TableHead>
                                        <TableHead>Subject / topic</TableHead>
                                        <TableHead>Question</TableHead>
                                        <TableHead>Choices</TableHead>
                                        <TableHead>Correct</TableHead>
                                        <TableHead>Result</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {preview.rows.map((row) => (
                                        <TableRow
                                            key={row.row}
                                            className={`align-top ${row.errors.length > 0 ? 'bg-destructive/5' : ''}`}
                                        >
                                            <TableCell>{row.row}</TableCell>
                                            <TableCell>
                                                {row.subject} / {row.topic}
                                            </TableCell>
                                            <TableCell className="max-w-xs whitespace-normal">
                                                {row.body.length > 100
                                                    ? `${row.body.slice(0, 100)}...`
                                                    : row.body}
                                            </TableCell>
                                            <TableCell>{row.choices}</TableCell>
                                            <TableCell>{row.correct}</TableCell>
                                            <TableCell className="whitespace-normal">
                                                {row.errors.length === 0 ? (
                                                    <Badge>OK</Badge>
                                                ) : (
                                                    <ul className="space-y-0.5 text-sm text-destructive">
                                                        {row.errors.map(
                                                            (error) => (
                                                                <li key={error}>
                                                                    {error}
                                                                </li>
                                                            ),
                                                        )}
                                                    </ul>
                                                )}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </section>
                )}
            </div>
        </>
    );
}

QuestionsImport.layout = {
    breadcrumbs: [
        {
            title: 'Question bank',
            href: questionRoutes.index(),
        },
        {
            title: 'Import',
            href: importRoutes.create(),
        },
    ],
};
