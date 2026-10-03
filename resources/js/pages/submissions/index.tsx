import { Head, Link, router, usePoll } from '@inertiajs/react';
import { ChevronRight, ClipboardCheck, Clock3 } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

type Submission = {
    id: number;
    version: number;
    status: string;
    submitted_at: string;
    user: { name: string; username: string; grade_level: string; section: string };
    lesson?: { number: number; title: string; level: string };
    case_study?: { number: number; title: string };
};
type Props = {
    submissions: { data: Submission[]; prev_page_url: string | null; next_page_url: string | null };
    awaitingReview: number;
    filters: { status?: string };
};

export default function Submissions({ submissions, awaitingReview, filters }: Props) {
    usePoll(15000, { only: ['submissions', 'awaitingReview'] });

    return <>
        <Head title="Submissions" />
        <main className="soft-grid min-h-full p-4 sm:p-6 lg:p-8">
            <div className="mx-auto max-w-6xl space-y-6">
                <header>
                    <Badge variant="outline">Review queue</Badge>
                    <h1 className="mt-3 text-3xl font-bold">Student submissions</h1>
                    <p className="mt-2 text-muted-foreground">Open submitted code, inspect its output, then mark it completed or request changes.</p>
                </header>
                <section className={`flex flex-wrap items-center justify-between gap-3 rounded-2xl border p-4 ${awaitingReview ? 'border-amber-300 bg-amber-50 text-amber-950' : 'border-emerald-200 bg-emerald-50 text-emerald-950'}`}>
                    <div className="flex items-center gap-3"><ClipboardCheck className="size-6" /><div><p className="font-bold">{awaitingReview ? `${awaitingReview} ${awaitingReview === 1 ? 'submission' : 'submissions'} awaiting your review` : 'No submissions awaiting review'}</p><p className="text-sm">{awaitingReview ? 'Students cannot move to the next lesson until their work is marked completed.' : 'New student submissions will appear here automatically.'}</p></div></div>
                    {awaitingReview > 0 && filters.status !== 'submitted' && <Button asChild className="bg-amber-800 hover:bg-amber-700"><Link href="/submissions?status=submitted">Show awaiting review</Link></Button>}
                </section>
                <Card className="border-0 shadow-sm ring-1 ring-slate-200">
                    <CardHeader className="flex-row items-center justify-between gap-3">
                        <CardTitle className="flex items-center gap-2"><ClipboardCheck className="text-indigo-600" /> Submissions</CardTitle>
                        <select value={filters.status ?? ''} onChange={(event) => router.get('/submissions', { status: event.target.value }, { preserveState: true })} className="h-10 rounded-md border bg-white px-3 text-sm" aria-label="Filter submissions">
                            <option value="">All statuses</option>
                            <option value="submitted">Awaiting review</option>
                            <option value="quiz_pending">Coding passed · quiz pending</option>
                            <option value="completed">Completed</option>
                            <option value="needs_revision">Needs revision</option>
                        </select>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {submissions.data.map((submission) => <Link key={submission.id} href={`/submissions/${submission.id}`} className={`flex flex-col justify-between gap-4 rounded-2xl border p-4 transition hover:border-indigo-300 hover:bg-indigo-50/40 sm:flex-row sm:items-center ${submission.status === 'submitted' ? 'border-amber-300 bg-amber-50/40' : ''}`}>
                            <div className="flex gap-4">
                                <span className={`grid size-11 shrink-0 place-items-center rounded-xl ${submission.status === 'submitted' ? 'bg-amber-100 text-amber-800' : 'bg-indigo-50 text-indigo-600'}`}><Clock3 className="size-5" /></span>
                                <div><p className="font-semibold">{submission.lesson ? `Lesson ${submission.lesson.number}: ${submission.lesson.title}` : `Case Study ${submission.case_study?.number}: ${submission.case_study?.title}`}</p><p className="mt-1 text-sm text-muted-foreground">{submission.user.name} · Grade {submission.user.grade_level}, {submission.user.section} · Version {submission.version}</p></div>
                            </div>
                            <div className="flex items-center gap-3"><Badge variant="outline" className={submission.status === 'submitted' ? 'border-amber-300 bg-amber-100 text-amber-900' : 'capitalize'}>{submission.status === 'submitted' ? 'Review needed' : submission.status.replace('_', ' ')}</Badge><ChevronRight className="size-5 text-muted-foreground" /></div>
                        </Link>)}
                        {!submissions.data.length && <div className="py-12 text-center text-muted-foreground">No submissions match this filter.</div>}
                        {(submissions.prev_page_url || submissions.next_page_url) && <div className="flex justify-between border-t pt-4">
                            {submissions.prev_page_url ? <Button asChild variant="outline"><Link href={submissions.prev_page_url}>Previous page</Link></Button> : <span />}
                            {submissions.next_page_url && <Button asChild variant="outline"><Link href={submissions.next_page_url}>Next page</Link></Button>}
                        </div>}
                    </CardContent>
                </Card>
            </div>
        </main>
    </>;
}

Submissions.layout = { breadcrumbs: [{ title: 'Submissions', href: '/submissions' }] };
