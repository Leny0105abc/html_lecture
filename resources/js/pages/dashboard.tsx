import { Head, Link, router, usePage, usePoll } from '@inertiajs/react';
import { useEffect } from 'react';
import { ArrowRight, BookCheck, BookOpen, Clock3, GraduationCap, Sparkles, Users } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

type TeacherSummary = { totalStudents: number; activeStudents: number; averageProgress: number; awaitingReview: number; completedActivities: number; needsRevision: number };
type StudentSummary = { progress: number; completed: number; remaining: number; caseStudies: number; level: string; lastActivity: string };
type StudentRow = { id: number; name: string; username: string; grade: string; section: string; completed: number; awaitingReview: number; progress: number; lastActivity: string; status: string; levelProgress: Record<string, number>; currentLesson: string; quizAverage: number | null; finalProjectStatus: string };
type Activity = { event: string; metadata?: { title?: string }; occurred_at: string };
type Review = { id: number; lesson: { id: number; number: number; title: string } | null; status: string; version: number; reviewed_at: string; comment: string | null; teacher: string | null };
type Props = { mode: 'teacher' | 'student'; summary: TeacherSummary | StudentSummary; students?: StudentRow[]; nextLesson?: { id: number; number: number; title: string; level: string } | null; recentActivity?: Activity[]; teacherReviews?: Review[] };

const statClass = 'border-0 bg-white shadow-sm ring-1 ring-slate-200/70';

export default function Dashboard({ mode, summary, students = [], nextLesson, recentActivity = [], teacherReviews = [] }: Props) {
    const { auth } = usePage().props;
    usePoll(15000, { only: ['teacherReviews', 'summary', 'nextLesson', 'recentActivity'] }, { autoStart: mode === 'student' });
    useEffect(() => {
        if (mode !== 'student') return;
        const refresh = () => router.reload({ only: ['teacherReviews', 'summary', 'nextLesson', 'recentActivity'] });
        window.addEventListener('focus', refresh);
        return () => window.removeEventListener('focus', refresh);
    }, [mode]);
    if (mode === 'teacher') {
        const s = summary as TeacherSummary;
        const stats = [
            ['Total students', s.totalStudents, Users, 'bg-indigo-50 text-indigo-600'],
            ['Active students', s.activeStudents, GraduationCap, 'bg-emerald-50 text-emerald-600'],
            ['Average progress', `${s.averageProgress}%`, BookCheck, 'bg-amber-50 text-amber-700'],
            ['Awaiting review', s.awaitingReview, Clock3, 'bg-rose-50 text-rose-600'],
        ] as const;
        return <>
            <Head title="Teacher Dashboard" />
            <main className="soft-grid min-h-full space-y-6 p-4 sm:p-6 lg:p-8">
                <section className="rounded-3xl bg-gradient-to-br from-indigo-700 to-slate-950 p-6 text-white shadow-xl sm:p-8">
                    <Badge className="mb-4 bg-amber-400 text-slate-950 hover:bg-amber-400">Teacher workspace</Badge>
                    <div className="flex flex-col justify-between gap-5 lg:flex-row lg:items-end">
                        <div><p className="mb-2 text-indigo-100">Class overview</p><h1 className="text-3xl font-bold tracking-tight sm:text-4xl">Good day, {auth.user.name}.</h1><p className="mt-3 max-w-2xl text-indigo-100">Track every learner, review submitted code, and keep the class moving through HTML and CSS.</p></div>
                        <Button asChild className="bg-amber-400 text-slate-950 hover:bg-amber-300"><Link href="/submissions?status=submitted">Review submissions <ArrowRight /></Link></Button>
                    </div>
                </section>
                <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    {stats.map(([label, value, Icon, color]) => <Card key={label} className={statClass}><CardContent className="flex items-center gap-4 p-5"><span className={`grid size-11 place-items-center rounded-2xl ${color}`}><Icon className="size-5" /></span><div><p className="text-sm text-muted-foreground">{label}</p><p className="text-2xl font-bold">{value}</p></div></CardContent></Card>)}
                </section>
                <Card className={statClass}>
                    <CardHeader className="flex-row items-center justify-between"><div><CardTitle>Student progress</CardTitle><p className="mt-1 text-sm text-muted-foreground">Course levels, current lesson, quiz scores, and final project</p></div><Button asChild variant="outline"><Link href="/students">View all</Link></Button></CardHeader>
                    <CardContent className="overflow-x-auto">
                        <table className="w-full min-w-[1150px] text-left text-sm">
                            <thead><tr className="border-b text-muted-foreground"><th className="pb-3">Student</th><th>Basic</th><th>Moderate</th><th>Advanced</th><th>Current lesson</th><th>Quiz average</th><th>Final project</th><th>Needs review</th><th /></tr></thead>
                            <tbody>{students.map((student) => <tr key={student.id} className="border-b last:border-0">
                                <td className="py-4"><p className="font-semibold">{student.name}</p><p className="text-xs text-muted-foreground">@{student.username} · {student.grade} {student.section}</p></td>
                                <td>{student.levelProgress.Basic}/5</td><td>{student.levelProgress.Moderate}/5</td><td>{student.levelProgress.Advanced}/5</td>
                                <td>{student.currentLesson}</td><td>{student.quizAverage === null ? '—' : String(student.quizAverage) + '%'}</td>
                                <td className="capitalize">{student.finalProjectStatus.replace('_', ' ')}</td>
                                <td>{student.awaitingReview > 0 ? <Badge className="bg-amber-100 text-amber-900 hover:bg-amber-100">{student.awaitingReview} pending</Badge> : '—'}</td>
                                <td className="text-right"><Button asChild size="sm" variant="ghost"><Link href={'/students/' + student.id}>Open</Link></Button></td>
                            </tr>)}</tbody>
                        </table>
                    </CardContent>
                </Card>
            </main>
        </>;
    }

    const s = summary as StudentSummary;
    const cards = [['Lessons complete', s.completed, BookCheck], ['Lessons remaining', s.remaining, BookOpen], ['Case studies', s.caseStudies, Sparkles]] as const;
    return <>
        <Head title="My Dashboard" />
        <main className="soft-grid min-h-full space-y-6 p-4 sm:p-6 lg:p-8">
            <section className="overflow-hidden rounded-3xl bg-slate-950 p-6 text-white shadow-xl sm:p-8">
                <div className="grid gap-8 lg:grid-cols-[1fr_340px] lg:items-center">
                    <div><Badge className="mb-4 bg-indigo-500 hover:bg-indigo-500">{s.level} level</Badge><h1 className="text-3xl font-bold sm:text-4xl">Welcome back, {auth.user.name.split(' ')[0]}!</h1><p className="mt-3 max-w-xl text-slate-300">Pick up where you left off. Every experiment brings you closer to building complete web pages.</p>{nextLesson && <Button asChild className="mt-6 bg-amber-400 text-slate-950 hover:bg-amber-300"><Link href={`/code-lab/${nextLesson.id}`}>Continue Lesson {nextLesson.number} <ArrowRight /></Link></Button>}</div>
                    <div className="rounded-2xl border border-white/10 bg-white/5 p-5"><div className="mb-3 flex items-end justify-between"><span className="text-sm text-slate-300">Course progress</span><strong className="text-3xl text-amber-300">{s.progress}%</strong></div><div className="h-3 overflow-hidden rounded-full bg-white/10"><div className="h-full rounded-full bg-gradient-to-r from-indigo-400 to-amber-300" style={{ width: `${s.progress}%` }} /></div><p className="mt-3 text-sm text-slate-400">{s.completed} of {s.completed + s.remaining} lessons completed</p></div>
                </div>
            </section>
            <section className="grid gap-4 sm:grid-cols-3">{cards.map(([label, value, Icon]) => <Card key={label} className={statClass}><CardContent className="flex items-center gap-4 p-5"><span className="grid size-11 place-items-center rounded-2xl bg-indigo-50 text-indigo-600"><Icon className="size-5" /></span><div><p className="text-sm text-muted-foreground">{label}</p><p className="text-2xl font-bold">{value}</p></div></CardContent></Card>)}</section>
            <section className="grid gap-6 lg:grid-cols-[1.4fr_1fr]">
                <Card className={`${statClass} lg:col-span-2`}><CardHeader><CardTitle>Teacher feedback & lesson confirmations</CardTitle><p className="text-sm text-muted-foreground">These remarks confirm your teacher has reviewed your submitted work.</p></CardHeader><CardContent className="space-y-3">
                    {teacherReviews.length ? teacherReviews.map(review => <article key={review.id} className={`rounded-xl border p-4 ${review.status === 'completed' ? 'border-emerald-200 bg-emerald-50' : 'border-amber-200 bg-amber-50'}`}>
                        <p className="font-semibold">Lesson {review.lesson?.number}: {review.lesson?.title ?? 'Lesson'}</p>
                        <p className="mt-1 text-sm font-semibold">{review.status === 'completed' ? '✓ Teacher confirmed: lesson completed' : review.status === 'needs_revision' ? 'Teacher requested changes' : 'Feedback on an earlier submission'}</p>
                        <p className="mt-1 text-xs text-muted-foreground">{review.teacher ?? 'Your teacher'} · Submission v{review.version} · {new Date(review.reviewed_at).toLocaleString()}</p>
                        <p className="mt-3 whitespace-pre-wrap break-words text-sm">{review.comment}</p>
                        {review.lesson && <Button asChild variant="outline" size="sm" className="mt-3"><Link href={`/code-lab/${review.lesson.id}`}>Open lesson</Link></Button>}
                    </article>) : <p className="text-sm text-muted-foreground">No teacher feedback yet. Your teacher’s remarks will appear here after reviewing your submission.</p>}
                </CardContent></Card>
                <Card className={statClass}><CardHeader><CardTitle>Up next</CardTitle></CardHeader><CardContent>{nextLesson ? <div className="rounded-2xl border border-indigo-100 bg-indigo-50/60 p-5"><div className="flex items-center justify-between gap-4"><div><Badge variant="outline">Lesson {nextLesson.number} · {nextLesson.level}</Badge><h2 className="mt-3 text-xl font-bold">{nextLesson.title}</h2><p className="mt-2 text-sm text-muted-foreground">Read the lesson, run and submit your code, then pass the five-question quiz to unlock the next lesson.</p></div><span className="hidden size-16 place-items-center rounded-2xl bg-indigo-600 text-2xl font-bold text-white sm:grid">{nextLesson.number}</span></div></div> : <p className="text-muted-foreground">You completed all lessons. Your case studies are ready!</p>}</CardContent></Card>
                <Card className={statClass}><CardHeader><CardTitle>Recent activity</CardTitle></CardHeader><CardContent className="space-y-4">{recentActivity.length ? recentActivity.map((item, index) => <div key={`${item.occurred_at}-${index}`} className="flex gap-3"><span className="mt-1 size-2 rounded-full bg-indigo-500" /><div><p className="text-sm font-medium capitalize">{item.event.replaceAll('_', ' ')}</p><p className="text-xs text-muted-foreground">{new Date(item.occurred_at).toLocaleString()}</p></div></div>) : <p className="text-sm text-muted-foreground">Your learning history will appear here.</p>}</CardContent></Card>
            </section>
        </main>
    </>;
}

Dashboard.layout = { breadcrumbs: [{ title: 'Dashboard', href: '/dashboard' }] };
