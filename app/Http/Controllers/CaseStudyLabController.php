<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\CaseStudy;
use App\Models\Lesson;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CaseStudyLabController extends Controller
{
    private function ensureUnlocked(Request $request, CaseStudy $caseStudy): void
    {
        $lessonCount = Lesson::where('is_published', true)->count();
        abort_unless($request->user()->lessonProgress()->where('status', 'completed')->count() >= $lessonCount, 403, 'Complete all lessons first.');
        $previous = CaseStudy::where('number', '<', $caseStudy->number)->where('is_published', true)->orderByDesc('number')->first();
        if ($previous) {
            abort_unless(DB::table('case_study_progress')->where('user_id', $request->user()->id)->where('case_study_id', $previous->id)->where('status', 'completed')->exists(), 403, 'Complete the previous case study first.');
        }
    }

    public function show(Request $request, CaseStudy $caseStudy): Response
    {
        $this->ensureUnlocked($request, $caseStudy);
        $progress = DB::table('case_study_progress')->where(['user_id' => $request->user()->id, 'case_study_id' => $caseStudy->id])->first();
        if (! $progress) {
            DB::table('case_study_progress')->insert([
                'user_id' => $request->user()->id, 'case_study_id' => $caseStudy->id, 'status' => 'in_progress',
                'html_code' => $caseStudy->starter_html, 'css_code' => $caseStudy->starter_css, 'version' => 1,
                'started_at' => now(), 'created_at' => now(), 'updated_at' => now(),
            ]);
            $progress = DB::table('case_study_progress')->where(['user_id' => $request->user()->id, 'case_study_id' => $caseStudy->id])->first();
        }
        $this->log($request->user()->id, 'case_study_started', $caseStudy);

        return Inertia::render('case-studies/show', [
            'caseStudy' => $caseStudy, 'workspace' => $progress,
            'feedback' => Submission::where('user_id', $request->user()->id)->where('case_study_id', $caseStudy->id)->with('feedback')->latest('version')->first(),
        ]);
    }

    public function save(Request $request, CaseStudy $caseStudy)
    {
        $this->ensureUnlocked($request, $caseStudy);
        $data = $request->validate(['html_code' => 'required|string|max:200000', 'css_code' => 'nullable|string|max:200000', 'version' => 'required|integer|min:1']);
        $progress = DB::transaction(function () use ($request, $caseStudy, $data) {
            $progress = DB::table('case_study_progress')->where(['user_id' => $request->user()->id, 'case_study_id' => $caseStudy->id])->lockForUpdate()->first();
            abort_unless($progress, 404);
            if ($progress->version !== (int) $data['version']) {
                throw ValidationException::withMessages(['version' => 'A newer save exists. Refresh before saving again.']);
            }
            DB::table('case_study_progress')->where('id', $progress->id)->update(['html_code' => $data['html_code'], 'css_code' => $data['css_code'] ?? '', 'version' => $progress->version + 1, 'last_saved_at' => now(), 'updated_at' => now()]);

            return DB::table('case_study_progress')->find($progress->id);
        });
        $this->log($request->user()->id, 'case_study_saved', $caseStudy);

        return response()->json(['version' => $progress->version, 'last_saved_at' => $progress->last_saved_at]);
    }

    public function submit(Request $request, CaseStudy $caseStudy)
    {
        $this->ensureUnlocked($request, $caseStudy);
        $data = $request->validate(['html_code' => 'required|string|max:200000', 'css_code' => 'nullable|string|max:200000', 'version' => 'required|integer|min:1']);
        $submission = DB::transaction(function () use ($request, $caseStudy, $data) {
            $progress = DB::table('case_study_progress')->where(['user_id' => $request->user()->id, 'case_study_id' => $caseStudy->id])->lockForUpdate()->first();
            abort_unless($progress, 404);
            if ($progress->version !== (int) $data['version']) {
                throw ValidationException::withMessages(['version' => 'Save your latest work before submitting.']);
            }
            $version = Submission::where('user_id', $request->user()->id)->where('case_study_id', $caseStudy->id)->max('version') + 1;
            $submission = Submission::create(['user_id' => $request->user()->id, 'case_study_id' => $caseStudy->id, 'version' => $version, 'status' => 'submitted', 'html_code' => $data['html_code'], 'css_code' => $data['css_code'] ?? '', 'submitted_at' => now()]);
            DB::table('case_study_progress')->where('id', $progress->id)->update(['status' => 'submitted', 'submitted_at' => now(), 'updated_at' => now()]);

            return $submission;
        });
        $this->log($request->user()->id, 'case_study_submitted', $caseStudy, ['submission_id' => $submission->id]);

        return response()->json(['message' => 'Case study submitted for review.']);
    }

    private function log(int $userId, string $event, CaseStudy $caseStudy, array $metadata = []): void
    {
        ActivityLog::create(['user_id' => $userId, 'event' => $event, 'subject_type' => CaseStudy::class, 'subject_id' => $caseStudy->id, 'metadata' => $metadata, 'occurred_at' => now()]);
    }
}
