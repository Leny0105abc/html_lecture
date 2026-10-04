import { Form, Head, Link, router, usePoll } from '@inertiajs/react';
import { ArrowLeft, ClipboardCheck, KeyRound, Unlock } from 'lucide-react';
import { useEffect } from 'react';
import StudentCredentials from '@/components/student-credentials';
import DeleteStudent from '@/components/delete-student';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

type Student = { id: number; name: string; username: string; grade_level: string; section: string; status: string; last_login_at: string | null };
type LessonRow = {
    lesson: { id: number; number: number; title: string; level: string };
    status: string;
    unlocked: boolean;
    quiz_score: number | null;
    quiz_passed_at: string | null;
    last_saved_at: string | null;
    submitted_at: string | null;
    submission: { id: number; version: number; status: string; submitted_at: string } | null;
};
type Submission = { id: number; version: number; status: string; submitted_at: string; lesson?: { title: string } };
type Activity = { id: number; event: string; occurred_at: string };
type Summary = { levels: Record<string, number>; currentLesson: string; quizAverage: number | null; finalProjectStatus: string };
type Props = { student: Student; lessons: LessonRow[]; summary: Summary; submissions: Submission[]; activity: Activity[] };

const statusLabel = (row: LessonRow) => {
    if (row.status === 'completed') return 'Completed';
    if (row.status === 'submitted') return 'Awaiting review';
    if (row.status === 'quiz_pending') return 'Coding passed · quiz pending';
    if (row.status === 'needs_revision') return 'Needs revision';
    if (row.status === 'in_progress') return 'Working · not submitted';
    return row.unlocked ? 'Ready to start' : 'Locked · not started';
};

const statusClass = (status: string) => {
    if (status === 'submitted') return 'border-amber-300 bg-amber-50 text-amber-900';
    if (status === 'completed') return 'border-emerald-300 bg-emerald-50 text-emerald-900';
    if (status === 'needs_revision') return 'border-rose-300 bg-rose-50 text-rose-900';
    return 'border-slate-200 bg-slate-50 text-slate-700';
};

export default function StudentShow({ student, lessons, summary, submissions, activity }: Props) {
    usePoll(15000, { only: ['lessons', 'summary', 'submissions', 'activity'] });
    useEffect(() => {
        const refresh = () => router.reload({ only: ['lessons', 'summary', 'submissions', 'activity'] });
        window.addEventListener('focus', refresh);
        return () => window.removeEventListener('focus', refresh);
    }, []);

    const awaitingReview = lessons.filter((row) => row.status === 'submitted');

    return <>
        <Head title={student.name} />
        <StudentCredentials />
        <main className="soft-grid min-h-full p-4 sm:p-6 lg:p-8">
            <div className="mx-auto max-w-6xl space-y-6">
                <Button asChild variant="ghost"><Link href="/students"><ArrowLeft /> Back to students</Link></Button>
                <section className="rounded-3xl bg-slate-950 p-6 text-white">
                    <div className="flex flex-col justify-between gap-5 sm:flex-row sm:items-center">
                        <div>
                            <Badge className="bg-indigo-500">{student.status}</Badge>
                            <h1 className="mt-3 text-3xl font-bold">{student.name}</h1>
                            <p className="mt-1 text-slate-300">@{student.username} · Grade {student.grade_level}, {student.section}</p>
                        </div>
                        <div className="flex flex-wrap gap-3">
                        <Form action={`/students/${student.id}/reset-password`} method="post" onSubmit={(event) => {
                            if (!window.confirm(`Issue a temporary password for ${student.name}? Their current password will stop working and they will be signed out.`)) event.preventDefault();
                        }}>
                            {({ processing }) => <Button disabled={processing} className="bg-amber-400 text-slate-950 hover:bg-amber-300"><KeyRound /> Issue temporary password</Button>}
                        </Form>
                        <DeleteStudent student={student} />
                        </div>
                    </div>
                </section>
                <section className="grid gap-3 sm:grid-cols-3 lg:grid-cols-6">
                    {(['Basic', 'Moderate', 'Advanced'] as const).map((level) => <div key={level} className="rounded-xl border bg-white p-4"><p className="text-xs text-muted-foreground">{level}</p><p className="mt-1 text-xl font-bold">{summary.levels[level] ?? 0}/5</p></div>)}
                    <div className="rounded-xl border bg-white p-4"><p className="text-xs text-muted-foreground">Current lesson</p><p className="mt-1 text-sm font-semibold">{summary.currentLesson}</p></div>
                    <div className="rounded-xl border bg-white p-4"><p className="text-xs text-muted-foreground">Quiz average</p><p className="mt-1 text-xl font-bold">{summary.quizAverage === null ? '—' : Math.round(summary.quizAverage * 20) + '%'}</p></div>
                    <div className="rounded-xl border bg-white p-4"><p className="text-xs text-muted-foreground">Final project</p><p className="mt-1 text-sm font-semibold capitalize">{summary.finalProjectStatus.replace('_', ' ')}</p></div>
                </section>
                {awaitingReview.length > 0 && <section className="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-amber-300 bg-amber-50 p-4 text-amber-950">
                    <div><p className="font-bold">{awaitingReview.length} lesson {awaitingReview.length === 1 ? 'submission needs' : 'submissions need'} your review</p><p className="text-sm">Open the submitted work, inspect the code and output, then mark it completed or request a revision.</p></div>
                    <Button asChild className="bg-amber-800 hover:bg-amber-700"><Link href={`/submissions/${awaitingReview[0].submission?.id}`}><ClipboardCheck /> Review now</Link></Button>
                </section>}
                <div className="grid gap-6 lg:grid-cols-[1.4fr_1fr]">
                    <Card>
                        <CardHeader><CardTitle>Lesson progress</CardTitle><p className="text-sm text-muted-foreground">Students must pass both coding checks and the 5-question quiz. Saved work alone does not complete a lesson.</p></CardHeader>
                        <CardContent className="space-y-3">
                            {lessons.map((row) => <div key={row.lesson.id} className={`rounded-xl border p-4 ${row.status === 'submitted' ? 'border-amber-300 bg-amber-50/50' : ''}`}>
                                <div className="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <p className="font-semibold">{row.lesson.level} Lesson {((row.lesson.number - 1) % 5) + 1}: {row.lesson.title}</p>
                                        <p className="text-sm text-muted-foreground">Quiz: {row.quiz_score === null ? 'Not taken' : `${row.quiz_score}/5${row.quiz_passed_at ? ' passed' : ' — retry needed'}`}{row.status === 'in_progress' && row.last_saved_at ? ` · Last saved ${new Date(row.last_saved_at).toLocaleString()}` : ''}</p>
                                    </div>
                                    <Badge variant="outline" className={statusClass(row.status)}>{statusLabel(row)}</Badge>
                                </div>
                                <div className="mt-3 flex flex-wrap items-center justify-between gap-2">
                                    <div className="text-xs text-muted-foreground">
                                        {row.submission ? `Submission v${row.submission.version} · ${new Date(row.submission.submitted_at).toLocaleString()}` : row.status === 'in_progress' ? 'No submission yet' : row.unlocked ? 'Available to the student' : 'Complete the previous lesson first'}
                                    </div>
                                    <div className="flex items-center gap-2">
                                        {row.submission && <Button asChild size="sm" variant={row.status === 'submitted' ? 'default' : 'outline'}>
                                            <Link href={`/submissions/${row.submission.id}`}>{row.status === 'submitted' ? 'Review work' : 'View work'}</Link>
                                        </Button>}
                                        {!row.unlocked && row.status !== 'completed' && <Form action={`/students/${student.id}/unlock/${row.lesson.id}`} method="post">
                                            <Button size="sm" variant="outline" aria-label={`Unlock lesson ${row.lesson.number}`}><Unlock /> Unlock</Button>
                                        </Form>}
                                    </div>
                                </div>
                            </div>)}
                        </CardContent>
                    </Card>
                    <div className="space-y-6">
                        <Card>
                            <CardHeader><CardTitle>Submission history</CardTitle></CardHeader>
                            <CardContent className="space-y-3">
                                {submissions.map((submission) => <Link key={submission.id} href={`/submissions/${submission.id}`} className="flex items-center justify-between gap-3 rounded-xl border p-3 hover:bg-slate-50">
                                    <div><p className="font-medium">{submission.lesson?.title ?? 'Case study'}</p><p className="text-xs text-muted-foreground">Version {submission.version} · {submission.status.replace('_', ' ')}</p></div>
                                    {submission.status === 'submitted' && <Badge className="bg-amber-100 text-amber-900 hover:bg-amber-100">Review</Badge>}
                                </Link>)}
                                {!submissions.length && <p className="text-sm text-muted-foreground">No work submitted yet.</p>}
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader><CardTitle>Recent activity</CardTitle></CardHeader>
                            <CardContent className="space-y-3">{activity.slice(0, 8).map((item) => <div key={item.id}><p className="text-sm font-medium capitalize">{item.event.replaceAll('_', ' ')}</p><p className="text-xs text-muted-foreground">{new Date(item.occurred_at).toLocaleString()}</p></div>)}</CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </main>
    </>;
}

StudentShow.layout = { breadcrumbs: [{ title: 'Students', href: '/students' }, { title: 'Profile', href: '#' }] };
