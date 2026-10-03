<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        if ($user->role === 'teacher') {
            $students = User::query()->where('role', 'student')->withCount([
                'lessonProgress as lessons_completed_count' => fn ($q) => $q->where('status', 'completed'),
                'submissions as awaiting_review_count' => fn ($q) => $q->where('status', 'submitted'),
            ])->with('lessonProgress:id,user_id,lesson_id,status,quiz_score')->latest('last_login_at')->take(8)->get();
            $courseLessons = Lesson::where('is_published', true)->orderBy('position')->get(['id', 'number', 'title', 'level']);
            $lessonCount = max($courseLessons->count(), 1);

            return Inertia::render('dashboard', [
                'mode' => 'teacher',
                'summary' => [
                    'totalStudents' => User::where('role', 'student')->count(),
                    'activeStudents' => User::where('role', 'student')->where('status', 'active')->count(),
                    'averageProgress' => round($students->avg(fn ($student) => ($student->lessons_completed_count / $lessonCount) * 100) ?? 0),
                    'awaitingReview' => Submission::where('status', 'submitted')->count(),
                    'completedActivities' => Submission::where('status', 'completed')->count(),
                    'needsRevision' => Submission::where('status', 'needs_revision')->count(),
                ],
                'students' => $students->map(function ($student) use ($courseLessons, $lessonCount) {
                    $progress = $student->lessonProgress->keyBy('lesson_id');
                    $current = $courseLessons->first(fn ($lesson) => ($progress->get($lesson->id)?->status) !== 'completed');
                    $scores = $student->lessonProgress->pluck('quiz_score')->filter(fn ($score) => $score !== null);

                    return [
                        'id' => $student->id, 'name' => $student->name, 'username' => $student->username,
                        'grade' => $student->grade_level, 'section' => $student->section,
                        'completed' => $student->lessons_completed_count,
                        'awaitingReview' => $student->awaiting_review_count,
                        'progress' => round(($student->lessons_completed_count / $lessonCount) * 100),
                        'lastActivity' => optional($student->last_login_at)->diffForHumans() ?? 'Never',
                        'status' => $student->status,
                        'levelProgress' => collect(['Basic', 'Moderate', 'Advanced'])->mapWithKeys(fn ($level) => [
                            $level => $courseLessons->where('level', $level)->filter(fn ($lesson) => $progress->get($lesson->id)?->status === 'completed')->count(),
                        ]),
                        'currentLesson' => $current ? $current->level.' '.((($current->number - 1) % 5) + 1) : 'Course complete',
                        'quizAverage' => $scores->count() ? round($scores->avg() * 20) : null,
                        'finalProjectStatus' => $progress->get($courseLessons->firstWhere('number', 15)?->id)?->status ?? 'not_started',
                    ];
                }),
            ]);
        }

        $lessons = Lesson::where('is_published', true)->orderBy('position')->get();
        $progress = $user->lessonProgress()->get()->keyBy('lesson_id');
        $completed = $progress->where('status', 'completed')->count();
        $nextLesson = $lessons->first(fn ($lesson) => ($progress[$lesson->id]->status ?? 'not_started') !== 'completed');
        $caseCompleted = \DB::table('case_study_progress')->where('user_id', $user->id)->where('status', 'completed')->count();

        return Inertia::render('dashboard', [
            'mode' => 'student',
            'summary' => [
                'progress' => $lessons->count() ? round(($completed / $lessons->count()) * 100) : 0,
                'completed' => $completed,
                'remaining' => max($lessons->count() - $completed, 0),
                'caseStudies' => $caseCompleted,
                'level' => $nextLesson?->level ?? 'Course complete',
                'lastActivity' => optional($user->activityLogs()->latest('occurred_at')->first()?->occurred_at)->diffForHumans() ?? 'Ready to begin',
            ],
            'nextLesson' => $nextLesson ? ['id' => $nextLesson->id, 'number' => $nextLesson->number, 'title' => $nextLesson->title, 'level' => $nextLesson->level] : null,
            'recentActivity' => $user->activityLogs()->latest('occurred_at')->take(5)->get(['event', 'metadata', 'occurred_at']),
        ]);
    }
}
