export type TeacherReview = {
    status: string;
    version: number;
    reviewed_at?: string | null;
    feedback?: { id: number; comment: string; teacher?: { name: string } | null }[];
};

export default function LessonTeacherReview({ review }: { review: TeacherReview | null }) {
    if (!review?.reviewed_at || !review.feedback?.length) return null;
    const completed = review.status === 'completed';
    const remarks = review.feedback.at(-1)!;

    return <section aria-label="Teacher feedback" aria-live="polite" className={`max-h-44 shrink-0 overflow-y-auto border-b px-4 py-3 text-sm ${completed ? 'border-emerald-200 bg-emerald-50 text-emerald-950' : 'border-amber-200 bg-amber-50 text-amber-950'}`}>
        <p className="font-bold">{completed ? '✓ Teacher confirmed: lesson completed' : review.status === 'needs_revision' ? 'Teacher feedback: changes needed' : 'Teacher feedback on an earlier submission'}</p>
        <p className="mt-1 text-xs">{remarks.teacher?.name ?? 'Your teacher'} · Submission v{review.version} · {new Date(review.reviewed_at).toLocaleString()}</p>
        <p className="mt-2 whitespace-pre-wrap break-words">{remarks.comment}</p>
        {completed && <p className="mt-2 font-medium">Your teacher has reviewed your work and marked this lesson completed.</p>}
    </section>;
}
