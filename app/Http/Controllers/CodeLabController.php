<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Lesson;
use App\Models\StudentLessonProgress;
use App\Models\Submission;
use App\Services\LessonCodeValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CodeLabController extends Controller
{
    private function ensureUnlocked(Request $request, Lesson $lesson): void
    {
        $progress = StudentLessonProgress::where('user_id', $request->user()->id)->where('lesson_id', $lesson->id)->first();
        $previous = Lesson::where('position', '<', $lesson->position)->where('is_published', true)->orderByDesc('position')->first();
        $previousComplete = ! $previous || StudentLessonProgress::where('user_id', $request->user()->id)
            ->where('lesson_id', $previous->id)->where('status', 'completed')->exists();
        abort_unless($previousComplete || $progress?->is_manually_unlocked, 403, 'Complete the previous lesson first.');
    }

    public function show(Request $request, Lesson $lesson, LessonCodeValidator $validator): Response
    {
        $this->ensureUnlocked($request, $lesson);
        $progress = StudentLessonProgress::firstOrCreate(
            ['user_id' => $request->user()->id, 'lesson_id' => $lesson->id],
            ['status' => 'in_progress', 'html_code' => $lesson->starter_html, 'css_code' => $lesson->starter_css, 'started_at' => now()]
        )->refresh();
        if ($progress->status === 'not_started') {
            $progress->update(['status' => 'in_progress', 'started_at' => now()]);
        }
        if (! $request->header('X-Inertia-Partial-Data')) {
            $this->log($request->user()->id, 'lesson_accessed', $lesson, ['title' => $lesson->title]);
        }
        $lessonData = $lesson->toArray();
        $lessonData['quiz'] = collect($lesson->quiz ?? [])->map(fn (array $question) => [
            'question' => $question['question'], 'choices' => $question['choices'],
        ])->all();
        $previousLesson = Lesson::where('position', '<', $lesson->position)
            ->where('is_published', true)->orderByDesc('position')->first();
        $nextLesson = Lesson::where('position', '>', $lesson->position)
            ->where('is_published', true)->orderBy('position')->first();
        $nextProgress = $nextLesson ? StudentLessonProgress::where('user_id', $request->user()->id)
            ->where('lesson_id', $nextLesson->id)->first() : null;

        $workspace = $progress->only('html_code', 'css_code', 'js_code', 'extra_files', 'status', 'version', 'last_saved_at', 'quiz_score', 'quiz_answers', 'quiz_passed_at', 'activity_passed_at');
        $workspace['html_code'] = $validator->normalizeLegacyHtml($lesson, $workspace['html_code'] ?? '');

        return Inertia::render('code-lab/show', [
            'lesson' => $lessonData,
            'workspace' => $workspace,
            'previousLesson' => $previousLesson?->only('id', 'number', 'title'),
            'nextLesson' => $nextLesson ? [
                'id' => $nextLesson->id, 'number' => $nextLesson->number, 'title' => $nextLesson->title,
                'unlocked' => $progress->status === 'completed' || (bool) $nextProgress?->is_manually_unlocked,
            ] : null,
            'feedback' => Submission::where('user_id', $request->user()->id)->where('lesson_id', $lesson->id)
                ->with('feedback.teacher:id,name')->latest('version')->first(),
            'aiConfigured' => filled(config('services.openai.key')),
        ]);
    }

    public function save(Request $request, Lesson $lesson, LessonCodeValidator $validator)
    {
        $this->ensureUnlocked($request, $lesson);
        $data = $this->validatedCode($request);
        $data['html_code'] = $validator->normalizeLegacyHtml($lesson, $data['html_code']);

        $progress = DB::transaction(function () use ($request, $lesson, $data) {
            $progress = StudentLessonProgress::where('user_id', $request->user()->id)->where('lesson_id', $lesson->id)->lockForUpdate()->firstOrFail();
            if ($progress->version !== (int) $data['version']) {
                throw ValidationException::withMessages(['version' => 'A newer save exists. Refresh before saving again.']);
            }
            $css = $lesson->level !== 'Advanced' ? '' : ($data['css_code'] ?? '');
            $extraFiles = $lesson->number === 15 ? ($data['extra_files'] ?? []) : [];
            $changed = $progress->html_code !== $data['html_code'] || $progress->css_code !== $css || ($progress->extra_files ?? []) !== $extraFiles;
            if ($changed && $progress->status !== 'completed') {
                Submission::where('user_id', $request->user()->id)->where('lesson_id', $lesson->id)
                    ->whereIn('status', ['submitted', 'quiz_pending'])->update(['status' => 'superseded']);
            }
            $progress->update([
                'html_code' => $data['html_code'], 'css_code' => $css, 'js_code' => $data['js_code'] ?? '',
                'extra_files' => $extraFiles,
                'version' => $progress->version + 1, 'last_saved_at' => now(),
                'status' => $changed && $progress->status !== 'completed' ? 'in_progress' : ($progress->status === 'not_started' ? 'in_progress' : $progress->status),
                'activity_passed_at' => $changed && $progress->status !== 'completed' ? null : $progress->activity_passed_at,
            ]);

            return $progress->fresh();
        });
        $this->log($request->user()->id, 'code_saved', $lesson, ['version' => $progress->version]);

        return response()->json(['version' => $progress->version, 'last_saved_at' => $progress->last_saved_at?->toIso8601String()]);
    }

    public function check(Request $request, Lesson $lesson, LessonCodeValidator $validator)
    {
        $this->ensureUnlocked($request, $lesson);
        $data = $this->validatedCode($request, false);
        $data['html_code'] = $validator->normalizeLegacyHtml($lesson, $data['html_code']);
        $errors = $validator->check($lesson, $data['html_code'], $lesson->level !== 'Advanced' ? '' : ($data['css_code'] ?? ''), $data['extra_files'] ?? []);

        return response()->json(['passed' => count($errors) === 0, 'issues' => $errors]);
    }

    public function submit(Request $request, Lesson $lesson, LessonCodeValidator $validator)
    {
        $this->ensureUnlocked($request, $lesson);
        $data = $this->validatedCode($request);
        $data['html_code'] = $validator->normalizeLegacyHtml($lesson, $data['html_code']);
        $css = $lesson->level !== 'Advanced' ? '' : ($data['css_code'] ?? '');
        $errors = $validator->check($lesson, $data['html_code'], $css, $data['extra_files'] ?? []);
        if ($errors) {
            return response()->json(['message' => 'Your activity needs a few changes.', 'issues' => $errors], 422);
        }

        $submission = DB::transaction(function () use ($request, $lesson, $data, $css) {
            $progress = StudentLessonProgress::where('user_id', $request->user()->id)->where('lesson_id', $lesson->id)->lockForUpdate()->firstOrFail();
            if ($progress->version !== (int) $data['version']) {
                throw ValidationException::withMessages(['version' => 'Please save or refresh your latest work before submitting.']);
            }
            $version = Submission::where('user_id', $request->user()->id)->where('lesson_id', $lesson->id)->max('version') + 1;
            $status = $progress->status === 'completed' || $lesson->auto_complete || ($lesson->quiz && $progress->quiz_passed_at)
                ? 'completed' : ($lesson->quiz ? 'quiz_pending' : 'submitted');
            Submission::where('user_id', $request->user()->id)->where('lesson_id', $lesson->id)
                ->whereIn('status', ['submitted', 'quiz_pending'])->update(['status' => 'superseded']);
            $submission = Submission::create([
                'user_id' => $request->user()->id, 'lesson_id' => $lesson->id, 'version' => $version, 'status' => $status,
                'html_code' => $data['html_code'], 'css_code' => $css, 'js_code' => $data['js_code'] ?? '',
                'extra_files' => $lesson->number === 15 ? ($data['extra_files'] ?? []) : [], 'submitted_at' => now(),
            ]);
            $progress->update([
                'status' => $status,
                'html_code' => $data['html_code'],
                'css_code' => $css,
                'extra_files' => $lesson->number === 15 ? ($data['extra_files'] ?? []) : [],
                'last_saved_at' => now(),
                'submitted_at' => now(),
                'activity_passed_at' => now(),
                'completed_at' => $status === 'completed' ? ($progress->completed_at ?? now()) : null,
            ]);

            return $submission;
        });
        $this->log($request->user()->id, 'activity_submitted', $lesson, ['submission_id' => $submission->id]);

        return response()->json(['status' => $submission->status, 'message' => $submission->status === 'completed' ? 'Activity and quiz complete. Next lesson unlocked!' : ($lesson->quiz ? 'Coding activity passed. Complete the quiz to unlock the next lesson.' : 'Activity submitted for review.')]);
    }

    public function quiz(Request $request, Lesson $lesson)
    {
        $this->ensureUnlocked($request, $lesson);
        $questions = $lesson->quiz ?? [];
        abort_unless(count($questions) === 5, 404);
        $data = $request->validate(['answers' => 'required|array|size:5', 'answers.*' => 'required|integer|between:0,3']);
        $score = 0;
        $feedback = [];
        foreach ($questions as $index => $question) {
            $correct = (int) $question['answer'];
            $isCorrect = (int) $data['answers'][$index] === $correct;
            $score += (int) $isCorrect;
            $feedback[] = [
                'correct' => $isCorrect,
                'correctAnswer' => $correct,
                'explanation' => $question['explanation'],
            ];
        }
        $progress = DB::transaction(function () use ($request, $lesson, $data, $score) {
            $progress = StudentLessonProgress::where('user_id', $request->user()->id)->where('lesson_id', $lesson->id)->lockForUpdate()->firstOrFail();
            $passed = $score >= 4 || (bool) $progress->quiz_passed_at;
            $complete = $passed && ($progress->activity_passed_at || $progress->status === 'completed');
            $progress->update([
                'quiz_answers' => $data['answers'], 'quiz_score' => $score,
                'quiz_passed_at' => $passed ? ($progress->quiz_passed_at ?? now()) : null,
                'status' => $complete ? 'completed' : $progress->status,
                'completed_at' => $complete ? ($progress->completed_at ?? now()) : $progress->completed_at,
            ]);
            if ($complete) {
                Submission::where('user_id', $request->user()->id)->where('lesson_id', $lesson->id)
                    ->whereIn('status', ['submitted', 'quiz_pending'])->update(['status' => 'completed']);
            }

            return $progress->fresh();
        });
        $this->log($request->user()->id, 'quiz_submitted', $lesson, ['score' => $score, 'passed' => $score >= 4]);

        return response()->json([
            'score' => $score, 'passed' => $score >= 4, 'completed' => $progress->status === 'completed',
            'feedback' => $feedback,
            'message' => $score >= 4 ? ($progress->status === 'completed' ? 'Lesson completed! Next lesson unlocked.' : 'Quiz passed! Submit your coding activity to finish.') : 'Score at least 4 out of 5 to pass. Review the explanations and try again.',
        ]);
    }

    private function validatedCode(Request $request, bool $requireVersion = true): array
    {
        $rules = [
            'html_code' => 'required|string|max:200000', 'css_code' => 'nullable|string|max:200000',
            'js_code' => 'nullable|string|max:100000',
            'extra_files' => 'sometimes|array',
            'extra_files.*' => 'string|max:200000',
        ];
        if ($requireVersion) {
            $rules['version'] = 'required|integer|min:1';
        }

        $data = $request->validate($rules);
        if (array_diff(array_keys($data['extra_files'] ?? []), ['about.html', 'gallery.html', 'contact.html'])) {
            throw ValidationException::withMessages(['extra_files' => 'Only about.html, gallery.html, and contact.html are supported.']);
        }

        return $data;
    }

    private function log(int $userId, string $event, Lesson $lesson, array $metadata = []): void
    {
        ActivityLog::create(['user_id' => $userId, 'event' => $event, 'subject_type' => Lesson::class, 'subject_id' => $lesson->id, 'metadata' => $metadata, 'occurred_at' => now()]);
    }
}
