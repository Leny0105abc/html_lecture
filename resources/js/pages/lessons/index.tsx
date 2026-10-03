import { Form, Head, Link, router, usePoll } from '@inertiajs/react';
import { BookOpen, CheckCircle2, ChevronRight, LockKeyhole, PlayCircle, Plus } from 'lucide-react';
import { useEffect } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Lesson = { id: number; number: number; title: string; level: string; status: string; unlocked: boolean; autoComplete: boolean; published: boolean };
type LevelProgress = Record<string, { completed: number; total: number }>;
const stateIcon = (lesson: Lesson) => lesson.status === 'completed' ? <CheckCircle2 className="text-emerald-600" /> : lesson.unlocked ? <PlayCircle className="text-indigo-600" /> : <LockKeyhole className="text-slate-400" />;

export default function Lessons({ lessons, levelProgress, isTeacher }: { lessons: Lesson[]; levelProgress: LevelProgress; isTeacher: boolean }) {
    usePoll(15000, { only: ['lessons', 'levelProgress'] }, { autoStart: !isTeacher });
    useEffect(() => {
        if (isTeacher) return;
        const refresh = () => router.reload({ only: ['lessons', 'levelProgress'] });
        window.addEventListener('focus', refresh);
        return () => window.removeEventListener('focus', refresh);
    }, [isTeacher]);

    return <><Head title="Lessons" /><main className="soft-grid min-h-full p-4 sm:p-6 lg:p-8"><div className="mx-auto max-w-6xl space-y-6">
        <header className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end"><div><Badge variant="outline">15-lesson learning path</Badge><h1 className="mt-3 text-3xl font-bold">HTML & CSS Lessons</h1><p className="mt-2 text-muted-foreground">Learn one concept at a time, practice it, and unlock what comes next.</p></div>{isTeacher && <Dialog><DialogTrigger asChild><Button><Plus /> Add lesson</Button></DialogTrigger><DialogContent><DialogHeader><DialogTitle>Create a lesson</DialogTitle><DialogDescription>Add a new activity to the end of the course.</DialogDescription></DialogHeader><Form action="/lessons" method="post" className="space-y-4"><div><Label>Title</Label><Input name="title" required /></div><div><Label>Level</Label><select name="level" className="mt-2 h-10 w-full rounded-md border bg-background px-3"><option>Basic</option><option>Moderate</option><option>Advanced</option></select></div><div><Label>Learning objective</Label><Input name="objectives[0]" required /></div><div><Label>Activity</Label><textarea name="activity" required className="mt-2 min-h-28 w-full rounded-md border bg-background p-3" /></div><Button type="submit" className="w-full">Create lesson</Button></Form></DialogContent></Dialog>}</header>
        {!isTeacher && <section className="grid gap-3 sm:grid-cols-3">{(['Basic', 'Moderate', 'Advanced'] as const).map((level) => <div key={level} className="rounded-2xl border bg-white p-4 shadow-sm"><div className="flex justify-between text-sm"><strong>{level}</strong><span>{levelProgress[level]?.completed ?? 0}/{levelProgress[level]?.total ?? 5}</span></div><div className="mt-3 h-2 overflow-hidden rounded-full bg-slate-100"><div className="h-full rounded-full bg-indigo-600" style={{ width: String(100 * (levelProgress[level]?.completed ?? 0) / Math.max(levelProgress[level]?.total ?? 5, 1)) + '%' }} /></div><p className="mt-2 text-xs text-muted-foreground">{level === 'Basic' ? 'HTML foundations' : level === 'Moderate' ? 'HTML pages and media' : 'HTML + CSS'}</p></div>)}</section>}
        {['Basic', 'Moderate', 'Advanced'].map((level) => <section key={level}><div className="mb-3 flex items-center gap-3"><span className={`size-3 rounded-full ${level === 'Basic' ? 'bg-emerald-500' : level === 'Moderate' ? 'bg-amber-500' : 'bg-indigo-600'}`} /><h2 className="text-lg font-bold">{level}</h2><span className="text-sm text-muted-foreground">{lessons.filter(l => l.level === level).length} lessons</span></div><div className="grid gap-3 md:grid-cols-2">{lessons.filter(l => l.level === level).map((lesson) => <Card key={lesson.id} className={`border-0 shadow-sm ring-1 ${lesson.unlocked ? 'bg-white ring-slate-200' : 'bg-slate-50 ring-slate-200/60'}`}><CardContent className="flex items-center gap-4 p-4"><span className="grid size-12 shrink-0 place-items-center rounded-2xl bg-slate-100">{stateIcon(lesson)}</span><div className="min-w-0 flex-1"><div className="flex items-center gap-2"><p className="text-xs font-bold uppercase tracking-wider text-muted-foreground">Lesson {((lesson.number - 1) % 5) + 1} of 5</p>{lesson.status !== 'not_started' && <Badge variant="outline" className="capitalize">{lesson.status.replace('_', ' ')}</Badge>}</div><h3 className="mt-1 truncate font-semibold">{lesson.title}</h3></div>{!isTeacher && lesson.unlocked ? <Button asChild size="icon" variant="ghost"><Link href={`/code-lab/${lesson.id}`} aria-label={`Open ${lesson.title}`}><ChevronRight /></Link></Button> : <BookOpen className="size-5 text-muted-foreground" />}</CardContent></Card>)}</div></section>)}
    </div></main></>;
}

Lessons.layout = { breadcrumbs: [{ title: 'Lessons', href: '/lessons' }] };
