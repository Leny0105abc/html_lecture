<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\StudentLessonProgress;
use App\Models\Submission;
use App\Models\TeacherFeedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SubmissionController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Submission::with(['user:id,name,username,grade_level,section', 'lesson:id,number,title,level', 'caseStudy:id,number,title'])
            ->orderByRaw("CASE WHEN status = 'submitted' THEN 0 ELSE 1 END")
            ->latest('submitted_at');
        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        return Inertia::render('submissions/index', [
            'submissions' => $query->paginate(20),
            'awaitingReview' => Submission::where('status', 'submitted')->count(),
            'filters' => $request->only('status'),
        ]);
    }

    public function show(Submission $submission): Response
    {
        $progress = $submission->lesson_id ? StudentLessonProgress::where('user_id', $submission->user_id)
            ->where('lesson_id', $submission->lesson_id)->first() : null;
        $quizRequired = count($submission->lesson?->quiz ?? []) === 5;

        return Inertia::render('submissions/show', [
            'submission' => $submission->load(['user', 'lesson', 'caseStudy', 'feedback']),
            'quizRequired' => $quizRequired,
            'quizScore' => $progress?->quiz_score,
            'canComplete' => ! $quizRequired || ($progress?->quiz_passed_at && $progress?->activity_passed_at) || $progress?->status === 'completed',
            'versions' => Submission::where('user_id', $submission->user_id)
                ->when($submission->lesson_id, fn ($q) => $q->where('lesson_id', $submission->lesson_id))
                ->when($submission->case_study_id, fn ($q) => $q->where('case_study_id', $submission->case_study_id))
                ->orderByDesc('version')->get(['id', 'version', 'status', 'submitted_at']),
        ]);
    }

    public function review(Request $request, Submission $submission)
    {
        $data = $request->validate([
            'status' => 'required|in:completed,needs_revision',
            'comment' => 'required_if:status,needs_revision|nullable|string|max:5000',
        ]);
        $data['comment'] = filled($data['comment'] ?? null) ? $data['comment'] : 'Good work. Activity completed.';
        if ($data['status'] === 'completed' && $submission->lesson?->quiz) {
            $progress = StudentLessonProgress::where('user_id', $submission->user_id)->where('lesson_id', $submission->lesson_id)->first();
            if ($progress?->status !== 'completed' && (! $progress?->activity_passed_at || ! $progress?->quiz_passed_at)) {
                throw ValidationException::withMessages(['status' => 'The student must pass both the coding activity and the 5-question quiz before this lesson can be completed.']);
            }
        }
        DB::transaction(function () use ($request, $submission, $data) {
            if ($submission->lesson_id) {
                StudentLessonProgress::where('user_id', $submission->user_id)->where('lesson_id', $submission->lesson_id)
                    ->lockForUpdate()->first();
            }
            $submission->update(['status' => $data['status'], 'reviewed_at' => now(), 'reviewed_by' => $request->user()->id]);
            TeacherFeedback::create(['submission_id' => $submission->id, 'teacher_id' => $request->user()->id, 'comment' => $data['comment']]);
            if ($submission->lesson_id) {
                StudentLessonProgress::where('user_id', $submission->user_id)->where('lesson_id', $submission->lesson_id)->update([
                    'status' => $data['status'], 'completed_at' => $data['status'] === 'completed' ? now() : null,
                ]);
            }
            if ($submission->case_study_id) {
                DB::table('case_study_progress')->where('user_id', $submission->user_id)->where('case_study_id', $submission->case_study_id)->update([
                    'status' => $data['status'], 'completed_at' => $data['status'] === 'completed' ? now() : null, 'updated_at' => now(),
                ]);
            }
            ActivityLog::create(['user_id' => $submission->user_id, 'event' => $data['status'] === 'completed' ? 'activity_completed' : 'revision_requested', 'subject_type' => Submission::class, 'subject_id' => $submission->id, 'metadata' => ['comment' => $data['comment']], 'occurred_at' => now()]);
        });

        return back()->with('success', 'Review saved and the student has been notified.');
    }
}
