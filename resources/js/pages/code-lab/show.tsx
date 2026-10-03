import { Head, Link, router, usePoll } from '@inertiajs/react';
import { ArrowRight, Bot, Check, ChevronLeft, Expand, Lightbulb, Play, RotateCcw, Save, Send } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { toast } from 'sonner';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';

type LessonGuide = { steps: string[]; focus: { code: string; meaning: string }[]; html_hint: string; css_hint: string; tip: string };
type QuizQuestion = { question: string; choices: string[] };
type Lesson = { id: number; number: number; title: string; level: string; objectives: string[]; introduction: string; explanation: string; syntax?: string; example_html?: string; example_css?: string; activity_html?: string; activity_css?: string; important_notes?: string; guided_practice: string; activity: string; expected_result: string; challenge?: string; completion_requirements: string; starter_html: string; starter_css: string; guide?: LessonGuide; quiz?: QuizQuestion[] };
type Workspace = { html_code: string; css_code: string; extra_files?: Record<string, string> | null; js_code?: string; status: string; version: number; last_saved_at?: string; quiz_score?: number | null; quiz_passed_at?: string | null; activity_passed_at?: string | null };
type Feedback = { status: string; feedback?: { comment: string }[] } | null;
type NextLesson = { id: number; number: number; title: string; unlocked: boolean } | null;
type PreviousLesson = { id: number; number: number; title: string } | null;
type QuizResult = { score: number; passed: boolean; completed: boolean; message: string; feedback: { correct: boolean; correctAnswer: number; explanation: string }[] };
type Props = { lesson: Lesson; workspace: Workspace; previousLesson: PreviousLesson; nextLesson: NextLesson; feedback: Feedback; aiConfigured: boolean };

const csrf = () => {
    const cookie = document.cookie.split('; ').find((item) => item.startsWith('XSRF-TOKEN='));
    return cookie ? decodeURIComponent(cookie.split('=').slice(1).join('=')) : '';
};
const requestHeaders = () => ({ 'Content-Type': 'application/json', 'X-XSRF-TOKEN': csrf(), Accept: 'application/json' });

export default function CodeLab({ lesson, workspace, previousLesson, nextLesson, feedback, aiConfigured }: Props) {
    const [html, setHtml] = useState(workspace.html_code ?? '');
    const [css, setCss] = useState(workspace.css_code ?? '');
    const [extraFiles, setExtraFiles] = useState<Record<string, string>>(workspace.extra_files ?? {});
    const [previewHtml, setPreviewHtml] = useState(html);
    const [previewCss, setPreviewCss] = useState(css);
    const [previewExtraFiles, setPreviewExtraFiles] = useState(extraFiles);
    const [previewFile, setPreviewFile] = useState('index.html');
    const [file, setFile] = useState('index.html');
    const [mobileTab, setMobileTab] = useState<'lesson' | 'code' | 'output' | 'ai'>('code');
    const [outputView, setOutputView] = useState<'live' | 'suggested' | 'guide'>('guide');
    const [saveState, setSaveState] = useState<'saved' | 'saving' | 'unsaved'>('saved');
    const [version, setVersion] = useState(workspace.version);
    const [question, setQuestion] = useState('');
    const [answer, setAnswer] = useState(aiConfigured ? 'Ask a question or choose a quick action. I’ll guide you without giving away the full answer.' : 'AI tutoring is not configured yet. The lesson, editor, preview, saving, and submission still work normally.');
    const [asking, setAsking] = useState(false);
    const [codeIssues, setCodeIssues] = useState<string[]>([]);
    const [codeChecked, setCodeChecked] = useState(false);
    const [quizAnswers, setQuizAnswers] = useState<Record<number, number>>({});
    const [quizResult, setQuizResult] = useState<QuizResult | null>(null);
    const [quizBusy, setQuizBusy] = useState(false);
    const iframe = useRef<HTMLIFrameElement>(null);
    const guideScroll = useRef<HTMLDivElement>(null);
    const quizRef = useRef<HTMLDivElement>(null);
    const firstRender = useRef(true);
    const currentVersion = useRef(version);
    currentVersion.current = version;
    usePoll(15000, { only: ['workspace', 'nextLesson'] });
    const isHtmlOnly = lesson.level !== 'Advanced';
    const isFinalProject = lesson.number === 15;
    const files = isFinalProject ? ['index.html', 'about.html', 'gallery.html', 'contact.html', 'styles.css'] : isHtmlOnly ? ['index.html'] : ['index.html', 'styles.css'];
    const withStyles = (markup: string, styles: string) => /<\/head\s*>/i.test(markup)
        ? markup.replace(/<\/head\s*>/i, `<style>${styles}</style></head>`)
        : `<!doctype html><html><head><style>${styles}</style></head><body>${markup}</body></html>`;
    const srcDoc = useMemo(() => withStyles(previewFile === 'index.html' ? previewHtml : previewExtraFiles[previewFile] ?? '', isHtmlOnly ? '' : previewCss), [previewFile, previewHtml, previewExtraFiles, previewCss, isHtmlOnly]);
    const expectedSrcDoc = useMemo(() => withStyles(lesson.activity_html ?? lesson.example_html ?? '', isHtmlOnly ? '' : lesson.activity_css ?? lesson.example_css ?? ''), [lesson.activity_html, lesson.activity_css, lesson.example_html, lesson.example_css, isHtmlOnly]);

    const save = async (quiet = false) => {
        setSaveState('saving');
        const response = await fetch(`/code-lab/${lesson.id}/save`, { method: 'PUT', headers: requestHeaders(), body: JSON.stringify({ html_code: html, css_code: isHtmlOnly ? '' : css, extra_files: isFinalProject ? extraFiles : {}, js_code: '', version: currentVersion.current }) });
        const data = await response.json();
        if (!response.ok) { setSaveState('unsaved'); if (!quiet) toast.error(data.message ?? 'Could not save your work.'); return false; }
        currentVersion.current = data.version; setVersion(data.version); setSaveState('saved'); if (!quiet) toast.success('Activity saved'); return true;
    };

    useEffect(() => {
        if (firstRender.current) { firstRender.current = false; return; }
        setSaveState('unsaved');
        const timer = window.setTimeout(() => { void save(true); }, 1800);
        return () => window.clearTimeout(timer);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [html, css, extraFiles]);

    useEffect(() => {
        const refreshApproval = () => router.reload({ only: ['workspace', 'nextLesson'] });
        window.addEventListener('focus', refreshApproval);
        return () => window.removeEventListener('focus', refreshApproval);
    }, []);

    const run = () => {
        setPreviewHtml(html); setPreviewCss(isHtmlOnly ? '' : css); setPreviewExtraFiles(extraFiles);
        setOutputView('live'); setMobileTab('output');
        void fetch(`/code-lab/${lesson.id}/check`, { method: 'POST', headers: requestHeaders(), body: JSON.stringify({ html_code: html, css_code: isHtmlOnly ? '' : css, extra_files: isFinalProject ? extraFiles : {} }) })
            .then((response) => response.json()).then((data) => { setCodeIssues(data.issues ?? []); setCodeChecked(true); })
            .catch(() => { setCodeIssues(['The preview ran, but the activity check could not connect. Try again.']); setCodeChecked(true); });
    };
    const showActivityGuide = () => { setOutputView('guide'); setMobileTab('output'); };
    const openQuiz = () => {
        setMobileTab('lesson');
        window.requestAnimationFrame(() => quizRef.current?.scrollIntoView({ behavior: 'smooth', block: 'start' }));
    };
    const reset = () => { if (window.confirm('Reset your editor to the starter code? Your current edits will be replaced.')) { setHtml(lesson.starter_html); setCss(lesson.starter_css); setExtraFiles({}); setCodeChecked(false); } };
    const submit = async () => {
        if (!window.confirm('Are you sure you want to submit this activity?')) return;
        if (saveState !== 'saved' && !(await save(true))) return;
        const response = await fetch(`/code-lab/${lesson.id}/submit`, { method: 'POST', headers: requestHeaders(), body: JSON.stringify({ html_code: html, css_code: isHtmlOnly ? '' : css, extra_files: isFinalProject ? extraFiles : {}, js_code: '', version: currentVersion.current }) });
        const data = await response.json();
        if (response.ok) {
            setCodeIssues([]); setCodeChecked(true); toast.success(data.message);
            router.reload({ only: ['workspace', 'nextLesson'] });
        } else {
            setCodeIssues(data.issues ?? []); setCodeChecked(true);
            toast.error(data.message ?? 'Submission failed.');
        }
    };
    const submitQuiz = async () => {
        if (!lesson.quiz || lesson.quiz.some((_, index) => quizAnswers[index] === undefined)) {
            toast.error('Answer all five questions first.'); return;
        }
        setQuizBusy(true);
        try {
            const response = await fetch(`/code-lab/${lesson.id}/quiz`, { method: 'POST', headers: requestHeaders(), body: JSON.stringify({ answers: lesson.quiz.map((_, index) => quizAnswers[index]) }) });
            const data = await response.json();
            if (!response.ok) { toast.error(data.message ?? 'Could not submit the quiz.'); return; }
            setQuizResult(data); toast[data.passed ? 'success' : 'error'](data.message);
            router.reload({ only: ['workspace', 'nextLesson'] });
        } catch { toast.error('Could not connect to the quiz. Try again.'); }
        finally { setQuizBusy(false); }
    };
    const ask = async (prompt = question) => {
        if (!prompt.trim()) return;
        if (!aiConfigured) { setAnswer('AI tutoring is not configured. Ask your teacher to add OPENAI_API_KEY on the server.'); return; }
        setAsking(true); setAnswer('Thinking through your code…');
        const response = await fetch(`/code-lab/${lesson.id}/ai`, { method: 'POST', headers: requestHeaders(), body: JSON.stringify({ question: prompt, html, css }) });
        const data = await response.json(); setAnswer(data.message ?? 'I could not answer that right now.'); setAsking(false); setQuestion('');
    };

    const guidePanel = lesson.guide && <div className="m-4 rounded-2xl border border-indigo-200 bg-indigo-50 p-4"><div className="flex items-center gap-2 font-bold text-indigo-950"><Lightbulb className="size-5 text-amber-500" />Quick guide</div><ol className="mt-3 space-y-2">{lesson.guide.steps.map((step, index) => <li key={step} className="flex gap-3 text-sm leading-5 text-slate-700"><span className="grid size-6 shrink-0 place-items-center rounded-full bg-indigo-600 text-xs font-bold text-white">{index + 1}</span><span>{step}</span></li>)}</ol><div className="mt-4"><p className="mb-2 text-xs font-bold uppercase tracking-wide text-indigo-700">Use these in the activity</p><div className="space-y-2">{lesson.guide.focus.map(item => <div key={item.code} className="flex items-start gap-2 rounded-lg bg-white px-3 py-2 text-xs text-slate-700"><code className="block max-w-full overflow-x-auto rounded bg-slate-900 px-1.5 py-0.5 text-amber-300">{item.code}</code><span>{item.meaning}</span></div>)}</div></div><div className="mt-4"><p className="mb-1 text-xs font-bold uppercase tracking-wide text-indigo-700">Related example (not the activity answer)</p><pre className="overflow-x-auto rounded-xl bg-slate-950 p-3 text-xs leading-5 text-slate-100"><code>{`HTML\n${lesson.guide.html_hint}${isHtmlOnly ? '' : `\n\nCSS\n${lesson.guide.css_hint}`}`}</code></pre></div></div>;
    const quizPanel = lesson.quiz?.length === 5 && <div ref={quizRef} className="mt-7 rounded-2xl border border-indigo-200 bg-indigo-50 p-4">
        <h2 className="font-bold text-indigo-950">5-question quiz</h2>
        <p className="mt-1 text-sm text-indigo-800">Score at least 4 out of 5 and pass the coding activity to complete this lesson.</p>
        {workspace.quiz_score !== null && workspace.quiz_score !== undefined && <p className="mt-2 text-sm font-semibold">Latest score: {workspace.quiz_score}/5 {workspace.quiz_passed_at ? '· Passed' : '· Try again'}</p>}
        {!workspace.quiz_passed_at && <div className="mt-4 space-y-5">
            {lesson.quiz.map((item, index) => <fieldset key={item.question} className="rounded-xl bg-white p-3">
                <legend className="font-semibold">{index + 1}. {item.question}</legend>
                <div className="mt-2 space-y-2">{item.choices.map((choice, choiceIndex) => <label key={choice} className="flex cursor-pointer gap-2 text-sm">
                    <input type="radio" name={`quiz-${lesson.id}-${index}`} checked={quizAnswers[index] === choiceIndex} onChange={() => setQuizAnswers((current) => ({ ...current, [index]: choiceIndex }))} />
                    <span>{String.fromCharCode(65 + choiceIndex)}. {choice}</span>
                </label>)}</div>
                {quizResult && <p className={`mt-2 text-sm ${quizResult.feedback[index].correct ? 'text-emerald-700' : 'text-rose-700'}`}>{quizResult.feedback[index].correct ? 'Correct.' : `Correct answer: ${String.fromCharCode(65 + quizResult.feedback[index].correctAnswer)}.`} {quizResult.feedback[index].explanation}</p>}
            </fieldset>)}
            <Button type="button" onClick={() => void submitQuiz()} disabled={quizBusy}>{quizBusy ? 'Checking…' : 'Submit quiz'}</Button>
        </div>}
        {workspace.quiz_passed_at && <p className="mt-3 text-sm text-emerald-800">Quiz passed. {workspace.status === 'completed' ? 'You can continue to the next lesson.' : 'Submit your coding activity to finish.'}</p>}
    </div>;
    const lessonPanel = <div className="h-full overflow-y-auto bg-white p-5 lg:p-6"><Badge>{lesson.level} · Lesson {((lesson.number - 1) % 5) + 1} of 5</Badge><h1 className="mt-3 text-2xl font-bold">{lesson.title}</h1><p className="mt-3 text-sm leading-6 text-muted-foreground">{lesson.introduction}</p><h2 className="mt-6 font-bold">Learning objectives</h2><ul className="mt-2 space-y-2">{lesson.objectives.map(o => <li key={o} className="flex gap-2 text-sm"><Check className="mt-0.5 size-4 shrink-0 text-emerald-600" />{o}</li>)}</ul><h2 className="mt-6 font-bold">How it works</h2><p className="mt-2 text-sm leading-6 text-muted-foreground">{lesson.explanation}</p>{lesson.syntax && <><h2 className="mt-5 font-bold">Syntax / reference</h2><pre className="code-surface mt-2 overflow-x-auto rounded-xl p-4 text-sm"><code>{lesson.syntax}</code></pre></>}{lesson.example_html && <><h2 className="mt-5 font-bold">Example code</h2><pre className="code-surface mt-2 max-h-64 overflow-auto rounded-xl p-4 text-xs"><code>{lesson.example_html}{!isHtmlOnly && lesson.example_css ? `\n\nCSS\n${lesson.example_css}` : ''} </code></pre></>}<div className="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-4"><div className="flex gap-2 font-semibold text-amber-900"><Lightbulb className="size-5" />Your activity</div><p className="mt-2 text-sm leading-6 text-amber-950">{lesson.activity}</p><Button type="button" size="sm" onClick={showActivityGuide} className="mt-4 bg-indigo-600 hover:bg-indigo-500">Open activity guide</Button></div>{guidePanel}<div className="mt-6 overflow-hidden rounded-2xl border border-indigo-200 bg-indigo-50"><div className="border-b border-indigo-200 px-4 py-3"><p className="font-bold text-indigo-950">Activity output</p><p className="mt-1 text-xs leading-5 text-indigo-700">Use this as your visual guide. You can also open it in the Output panel for a larger view.</p></div><iframe title={`Activity output for ${lesson.title}`} sandbox="" srcDoc={expectedSrcDoc} className="h-64 w-full bg-white" /></div><h2 className="mt-6 font-bold">Expected result</h2><p className="mt-2 text-sm leading-6 text-muted-foreground">{lesson.expected_result}</p>{lesson.challenge && <><h2 className="mt-6 font-bold">Challenge</h2><p className="mt-2 text-sm leading-6 text-muted-foreground">{lesson.challenge}</p></>}{quizPanel}</div>;
    const editorValue = file === 'index.html' ? html : file === 'styles.css' ? css : extraFiles[file] ?? lesson.starter_html;
    const editFile = (value: string) => {
        if (file === 'index.html') setHtml(value);
        else if (file === 'styles.css') setCss(value);
        else setExtraFiles((current) => ({ ...current, [file]: value }));
        setCodeChecked(false);
    };
    const codePanel = <div className="flex h-full min-h-[480px] flex-col bg-slate-950">
        <div className="flex items-center justify-between border-b border-white/10 px-3">
            <div className="flex max-w-[85%] overflow-x-auto">{files.map((name) => <button key={name} type="button" onClick={() => setFile(name)} className={`shrink-0 border-b-2 px-3 py-3 text-xs ${file === name ? 'border-amber-400 text-white' : 'border-transparent text-slate-400'}`}>{name}</button>)}</div>
            <span className="text-xs text-slate-400">{saveState === 'saving' ? 'Saving…' : saveState === 'saved' ? 'Saved' : 'Unsaved'}</span>
        </div>
        <label className="sr-only" htmlFor="code-editor">{file} editor</label>
        <textarea id="code-editor" value={editorValue} onChange={(event) => editFile(event.target.value)} spellCheck={false} className="code-surface min-h-0 flex-1 resize-none border-0 p-5 text-[14px] leading-6 outline-none" />
    </div>;
    const outputPanel = <div className="flex h-full min-h-0 flex-col bg-white"><div className="flex shrink-0 items-center justify-between gap-2 border-b px-3 py-2"><div className="grid min-w-0 flex-1 grid-cols-3 rounded-lg bg-slate-100 p-1"><button type="button" onClick={() => setOutputView('live')} className={`rounded-md px-2 py-1.5 text-xs font-semibold ${outputView === 'live' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-500'}`}>My live output</button><button type="button" onClick={() => setOutputView('suggested')} className={`rounded-md px-2 py-1.5 text-xs font-semibold ${outputView === 'suggested' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-500'}`}>Activity output</button><button type="button" onClick={() => setOutputView('guide')} className={`rounded-md px-2 py-1.5 text-xs font-semibold ${outputView === 'guide' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-500'}`}>Guide</button></div><Button size="icon" variant="ghost" onClick={() => iframe.current?.requestFullscreen()} aria-label="Full screen output"><Expand /></Button></div><div className="flex shrink-0 items-center justify-between gap-2 border-b bg-indigo-50 px-4 py-2 text-xs text-indigo-800"><span>{outputView === 'live' ? 'Your latest result. Press Run after changing your code.' : outputView === 'suggested' ? 'Compare this activity result with your own live output.' : 'Follow the steps and use the example as a hint.'}</span>{outputView === 'guide' && <button type="button" onClick={() => guideScroll.current?.scrollTo({ top: guideScroll.current.scrollHeight, behavior: 'smooth' })} className="shrink-0 font-semibold underline underline-offset-2">See example</button>}</div>{isFinalProject && outputView === 'live' && <div className="flex shrink-0 gap-1 overflow-x-auto border-b px-2 py-1">{files.filter((name) => name.endsWith('.html')).map((name) => <button key={name} type="button" onClick={() => setPreviewFile(name)} className={`rounded px-2 py-1 text-xs ${previewFile === name ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-700'}`}>{name}</button>)}</div>}{outputView === 'live' ? <iframe ref={iframe} title="Student code preview" sandbox="allow-same-origin" srcDoc={srcDoc} onLoad={() => { if (!isFinalProject) return; const doc = iframe.current?.contentDocument; doc?.addEventListener('click', (event) => { const target = event.target as HTMLElement | null; const link = target?.closest?.('a[href]'); const href = link?.getAttribute('href'); if (href && files.includes(href) && href.endsWith('.html')) { event.preventDefault(); setPreviewFile(href); } }); }} className="min-h-0 flex-1 bg-white" /> : outputView === 'suggested' ? <iframe ref={iframe} title={`Activity output for ${lesson.title}`} sandbox="" srcDoc={expectedSrcDoc} className="min-h-0 flex-1 bg-white" /> : <div ref={guideScroll} className="min-h-0 flex-1 overscroll-contain overflow-y-auto pb-4"><div className="m-4 rounded-2xl border border-amber-200 bg-amber-50 p-4"><div className="flex items-center gap-2 font-bold text-amber-900"><Lightbulb className="size-5" />Your activity to complete</div><p className="mt-2 text-sm leading-6 text-amber-950">{lesson.activity}</p></div>{guidePanel}</div>}</div>;
    const aiPanel = <div className="flex h-full min-h-[480px] flex-col bg-white p-4"><div className="flex items-center gap-2"><span className="grid size-10 place-items-center rounded-xl bg-indigo-600 text-white"><Bot /></span><div><h2 className="font-bold">Learning tutor</h2><p className="text-xs text-muted-foreground">Hints and explanations, not answers</p></div></div><div className="mt-4 flex-1 overflow-y-auto rounded-2xl bg-indigo-50 p-4 text-sm leading-6 text-slate-700">{answer}</div><div className="mt-3 flex flex-wrap gap-2">{['Explain my code', 'Find the error', 'Give me a hint'].map(action => <Button key={action} size="sm" variant="outline" onClick={() => void ask(action)} disabled={asking}>{action}</Button>)}</div><div className="mt-3 flex gap-2"><input value={question} onChange={e => setQuestion(e.target.value)} onKeyDown={e => { if (e.key === 'Enter') void ask(); }} placeholder="Ask about your code…" className="min-w-0 flex-1 rounded-xl border px-3 text-sm" /><Button size="icon" onClick={() => void ask()} disabled={asking} aria-label="Ask AI tutor"><Send /></Button></div></div>;
    const progressNotice = nextLesson?.unlocked
        ? <div className="flex shrink-0 flex-wrap items-center justify-between gap-2 border-b border-emerald-200 bg-emerald-50 px-4 py-2 text-sm text-emerald-950"><span>Lesson {nextLesson.number} is ready.</span><Button asChild size="sm" className="bg-emerald-700 hover:bg-emerald-600"><Link href={`/code-lab/${nextLesson.id}`}>Continue to Lesson {nextLesson.number} <ArrowRight /></Link></Button></div>
        : workspace.status === 'needs_revision'
            ? <div className="shrink-0 border-b border-rose-200 bg-rose-50 px-4 py-2 text-sm text-rose-950">Your teacher requested changes. Update your code, then submit the activity again.</div>
            : workspace.activity_passed_at && !workspace.quiz_passed_at
                ? <div className="flex shrink-0 flex-wrap items-center justify-between gap-2 border-b border-amber-200 bg-amber-50 px-4 py-2 text-sm text-amber-950"><span>Coding activity passed. Score at least 4/5 on the quiz to unlock the next lesson.</span><Button size="sm" variant="outline" onClick={openQuiz}>Go to quiz</Button></div>
            : workspace.quiz_passed_at && !workspace.activity_passed_at
                ? <div className="shrink-0 border-b border-indigo-200 bg-indigo-50 px-4 py-2 text-sm text-indigo-950">Quiz passed. Finish and submit your coding activity to unlock the next lesson.</div>
            : workspace.status === 'submitted'
                ? <div className="flex shrink-0 flex-wrap items-center justify-between gap-2 border-b border-amber-200 bg-amber-50 px-4 py-2 text-sm text-amber-950"><span>Submitted for teacher review. This page checks for approval automatically.</span><Button type="button" size="sm" variant="outline" onClick={() => router.reload({ only: ['workspace', 'nextLesson'] })}>Check approval</Button></div>
            : workspace.status === 'completed' && !nextLesson
                ? <div className="flex shrink-0 flex-wrap items-center justify-between gap-2 border-b border-emerald-200 bg-emerald-50 px-4 py-2 text-sm text-emerald-950"><span>All lessons completed.</span><Button asChild size="sm" className="bg-emerald-700 hover:bg-emerald-600"><Link href="/case-studies">Continue to case studies <ArrowRight /></Link></Button></div>
                : <div className="shrink-0 border-b border-indigo-200 bg-indigo-50 px-4 py-2 text-sm text-indigo-950">Your work is not submitted yet. Run your code, fix the activity hints, submit it, and pass the quiz to unlock the next lesson.</div>;

    return <><Head title={`Lesson ${lesson.number}: ${lesson.title}`} /><main className="flex h-[calc(100dvh-4rem)] min-h-0 flex-col overflow-hidden bg-slate-100"><header className="flex shrink-0 flex-wrap items-center justify-between gap-3 border-b bg-white px-3 py-3 sm:px-5"><div className="flex min-w-0 items-center gap-2"><Button asChild size="icon" variant="ghost"><Link href="/lessons" aria-label="Back to lessons"><ChevronLeft /></Link></Button><div className="min-w-0"><p className="truncate text-sm font-bold">Lesson {lesson.number}: {lesson.title}</p><p className="text-xs text-muted-foreground capitalize">Status: {workspace.status.replace('_', ' ')}</p></div></div><div className="flex flex-wrap gap-2">{previousLesson && <Button asChild size="sm" variant="outline"><Link href={`/code-lab/${previousLesson.id}`}>Previous lesson</Link></Button>}<Button size="sm" variant="outline" onClick={reset} aria-label="Reset editor"><RotateCcw /> <span className="hidden sm:inline">Reset</span></Button><Button size="sm" variant="outline" onClick={() => void save()} aria-label="Save activity"><Save /> <span className="hidden sm:inline">Save activity</span></Button><Button size="sm" onClick={run} className="bg-emerald-600 hover:bg-emerald-500"><Play /> Run</Button><Button size="sm" onClick={() => void submit()} className="bg-indigo-600 hover:bg-indigo-500">{workspace.status === 'completed' ? 'Resubmit activity' : 'Submit activity'}</Button>{lesson.quiz?.length === 5 && <Button size="sm" variant="outline" onClick={openQuiz}>Quiz</Button>}{nextLesson && (nextLesson.unlocked ? <Button asChild size="sm"><Link href={`/code-lab/${nextLesson.id}`}>Next lesson <ArrowRight /></Link></Button> : <Button size="sm" disabled>Next lesson locked</Button>)}</div></header>{progressNotice}{codeChecked && <div className={`max-h-32 shrink-0 overflow-y-auto border-b px-4 py-2 text-sm ${codeIssues.length ? 'border-amber-200 bg-amber-50 text-amber-950' : 'border-emerald-200 bg-emerald-50 text-emerald-950'}`}>{codeIssues.length ? <><strong>Activity hints:</strong><ul className="ml-5 list-disc">{codeIssues.map((issue) => <li key={issue}>{issue}</li>)}</ul></> : 'Coding checks passed. Submit the activity to record it.'}</div>}{feedback?.status === 'needs_revision' && <div className="bg-amber-100 px-5 py-2 text-sm text-amber-950"><strong>Revision requested:</strong> {feedback.feedback?.at(-1)?.comment}</div>}<nav className="grid shrink-0 grid-cols-4 border-b bg-white lg:hidden">{(['lesson', 'code', 'output', 'ai'] as const).map(tab => <button key={tab} onClick={() => setMobileTab(tab)} className={`py-3 text-sm font-medium capitalize ${mobileTab === tab ? 'border-b-2 border-indigo-600 text-indigo-600' : 'text-muted-foreground'}`}>{tab}</button>)}</nav><div className="min-h-0 flex-1 lg:grid lg:grid-cols-[minmax(260px,.7fr)_minmax(360px,1fr)_minmax(360px,1fr)] lg:gap-px lg:bg-slate-300"><section className={`${mobileTab === 'lesson' ? 'block' : 'hidden'} h-full min-h-0 lg:block`}>{lessonPanel}</section><section className={`${mobileTab === 'code' ? 'block' : 'hidden'} h-full min-h-0 lg:block`}>{codePanel}</section><section className={`${mobileTab === 'output' ? 'block' : 'hidden'} h-full min-h-0 lg:block`}>{outputPanel}</section><section className={`${mobileTab === 'ai' ? 'block' : 'hidden'} h-full min-h-0 lg:hidden`}>{aiPanel}</section></div></main></>;
}

CodeLab.layout = null;
