import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Check, Play, Save, Send } from 'lucide-react';
import { useMemo, useRef, useState } from 'react';
import { toast } from 'sonner';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

type CaseStudy = { id: number; number: number; title: string; scenario: string; objectives: string[]; requirements: string[]; instructions: string; concepts: string[]; expected_features: string[]; rubric: Record<string, number>; starter_html: string; starter_css: string };
type Workspace = { html_code: string; css_code: string; status: string; version: number; last_saved_at?: string };
type Feedback = { status: string; feedback?: { comment: string }[] } | null;
const csrf = () => {
    const cookie = document.cookie.split('; ').find((item) => item.startsWith('XSRF-TOKEN='));
    return cookie ? decodeURIComponent(cookie.split('=').slice(1).join('=')) : '';
};
const requestHeaders = () => ({ 'Content-Type': 'application/json', 'X-XSRF-TOKEN': csrf(), Accept: 'application/json' });

export default function CaseStudyWorkspace({ caseStudy, workspace, feedback }: { caseStudy: CaseStudy; workspace: Workspace; feedback: Feedback }) {
    const [html, setHtml] = useState(workspace.html_code ?? '');
    const [css, setCss] = useState(workspace.css_code ?? '');
    const [previewHtml, setPreviewHtml] = useState(html);
    const [previewCss, setPreviewCss] = useState(css);
    const [file, setFile] = useState<'html' | 'css'>('html');
    const [version, setVersion] = useState(workspace.version);
    const [saving, setSaving] = useState(false);
    const versionRef = useRef(version); versionRef.current = version;
    const srcDoc = useMemo(() => `<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width"><style>${previewCss}</style></head><body>${previewHtml}</body></html>`, [previewHtml, previewCss]);
    const save = async () => { setSaving(true); const response = await fetch(`/case-studies/${caseStudy.id}/save`, { method: 'PUT', headers: requestHeaders(), body: JSON.stringify({ html_code: html, css_code: css, version: versionRef.current }) }); const data = await response.json(); setSaving(false); if (!response.ok) { toast.error(data.message ?? 'Could not save.'); return false; } versionRef.current = data.version; setVersion(data.version); toast.success('Case study saved'); return true; };
    const submit = async () => { if (!window.confirm('Are you sure you want to submit this case study?')) return; if (!(await save())) return; const response = await fetch(`/case-studies/${caseStudy.id}/submit`, { method: 'POST', headers: requestHeaders(), body: JSON.stringify({ html_code: html, css_code: css, version: versionRef.current }) }); const data = await response.json(); response.ok ? toast.success(data.message) : toast.error(data.message ?? 'Could not submit.'); };
    return <><Head title={`Case Study ${caseStudy.number}: ${caseStudy.title}`} /><main className="soft-grid min-h-full p-4 sm:p-6 lg:p-8"><div className="mx-auto max-w-[1500px] space-y-5"><header className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end"><div><Button asChild variant="ghost" className="mb-2 -ml-3"><Link href="/case-studies"><ArrowLeft /> Case studies</Link></Button><div><Badge>Case Study {caseStudy.number}</Badge><h1 className="mt-3 text-3xl font-bold">{caseStudy.title}</h1><p className="mt-2 max-w-3xl text-muted-foreground">{caseStudy.scenario}</p></div></div><div className="flex gap-2"><Button variant="outline" onClick={() => void save()} disabled={saving}><Save /> {saving ? 'Saving…' : 'Save'}</Button><Button onClick={() => { setPreviewHtml(html); setPreviewCss(css); }} className="bg-emerald-600 hover:bg-emerald-500"><Play /> Run</Button><Button onClick={() => void submit()}><Send /> Submit</Button></div></header>{feedback?.status === 'needs_revision' && <div className="rounded-xl bg-amber-100 p-4 text-sm text-amber-950"><strong>Revision requested:</strong> {feedback.feedback?.at(-1)?.comment}</div>}<div className="grid gap-5 xl:grid-cols-[330px_1fr_1fr]"><Card><CardHeader><CardTitle>Project brief</CardTitle></CardHeader><CardContent className="space-y-5 text-sm"><div><h2 className="font-bold">Requirements</h2><ul className="mt-2 space-y-2">{caseStudy.requirements.map(item => <li key={item} className="flex gap-2"><Check className="mt-0.5 size-4 shrink-0 text-emerald-600" />{item}</li>)}</ul></div><div><h2 className="font-bold">Instructions</h2><p className="mt-2 leading-6 text-muted-foreground">{caseStudy.instructions}</p></div><div><h2 className="font-bold">Rubric</h2><div className="mt-2 space-y-2">{Object.entries(caseStudy.rubric).map(([label, score]) => <div key={label} className="flex justify-between rounded-lg bg-slate-50 px-3 py-2"><span>{label}</span><strong>{score}%</strong></div>)}</div></div></CardContent></Card><Card className="overflow-hidden"><div className="flex border-b bg-slate-950">{(['html', 'css'] as const).map(tab => <button key={tab} onClick={() => setFile(tab)} className={`px-5 py-3 text-sm ${file === tab ? 'border-b-2 border-amber-400 text-white' : 'text-slate-400'}`}>{tab === 'html' ? 'index.html' : 'style.css'}</button>)}</div><textarea aria-label={`${file.toUpperCase()} editor`} value={file === 'html' ? html : css} onChange={e => file === 'html' ? setHtml(e.target.value) : setCss(e.target.value)} spellCheck={false} className="code-surface min-h-[640px] w-full resize-none border-0 p-5 text-sm leading-6 outline-none" /></Card><Card className="overflow-hidden"><CardHeader><CardTitle>Live output</CardTitle></CardHeader><iframe title="Case study preview" sandbox="" srcDoc={srcDoc} className="min-h-[640px] w-full border-t bg-white" /></Card></div></div></main></>;
}
CaseStudyWorkspace.layout = { breadcrumbs: [{ title: 'Case Studies', href: '/case-studies' }, { title: 'Workspace', href: '#' }] };
