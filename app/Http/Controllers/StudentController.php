<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\StudentLessonProgress;
use App\Models\Submission;
use App\Models\User;
use App\Services\StudentAccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    public function index(Request $request): Response
    {
        $query = User::where('role', 'student')->withCount([
            'lessonProgress as completed_count' => fn ($q) => $q->where('status', 'completed'),
            'submissions as awaiting_review_count' => fn ($q) => $q->where('status', 'submitted'),
        ]);
        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('username', 'like', "%{$search}%"));
        }
        if ($grade = $request->string('grade')->toString()) {
            $query->where('grade_level', $grade);
        }
        if ($section = $request->string('section')->toString()) {
            $query->where('section', $section);
        }
        $totalLessons = max(Lesson::where('is_published', true)->count(), 1);

        return Inertia::render('students/index', [
            'students' => $query->orderBy('last_name')->paginate(20)->through(fn ($student) => [
                'id' => $student->id, 'name' => $student->name, 'username' => $student->username,
                'grade' => $student->grade_level, 'section' => $student->section, 'status' => $student->status,
                'completed' => $student->completed_count, 'progress' => round($student->completed_count / $totalLessons * 100),
                'awaitingReview' => $student->awaiting_review_count,
                'lastLogin' => optional($student->last_login_at)->diffForHumans() ?? 'Never',
            ]),
            'filters' => $request->only('search', 'grade', 'section'),
        ]);
    }

    public function store(Request $request, StudentAccountService $accounts)
    {
        $data = $request->validate([
            'first_name' => 'required|string|max:80', 'middle_name' => 'nullable|string|max:80',
            'last_name' => 'required|string|max:80', 'grade_level' => 'required|string|max:30',
            'section' => 'required|string|max:50', 'lrn' => 'nullable|string|max:30|unique:users,lrn',
        ]);
        $username = $accounts->username($data['first_name'], $data['last_name']);
        $password = $accounts->temporaryPassword();
        $name = collect([$data['first_name'], $data['middle_name'] ?? null, $data['last_name']])->filter()->join(' ');

        User::create($data + [
            'name' => $name, 'username' => $username, 'email' => $username.'@student.local',
            'password' => Hash::make($password), 'role' => 'student', 'status' => 'active',
            'must_change_password' => true, 'email_verified_at' => now(),
        ]);

        return back()->with('credentials', ['name' => $name, 'username' => $username, 'password' => $password]);
    }

    public function show(User $student): Response
    {
        abort_unless($student->role === 'student', 404);
        $progress = $student->lessonProgress()->get()->keyBy('lesson_id');
        $latestSubmissions = Submission::where('user_id', $student->id)->whereNotNull('lesson_id')
            ->orderByDesc('version')->get()->unique('lesson_id')->keyBy('lesson_id');
        $completedPrevious = true;
        $lessons = Lesson::where('is_published', true)->orderBy('position')->get()
            ->map(function (Lesson $lesson) use ($progress, $latestSubmissions, &$completedPrevious) {
                $item = $progress->get($lesson->id);
                $status = $item?->status ?? 'not_started';
                $unlocked = $completedPrevious || (bool) $item?->is_manually_unlocked;
                $completedPrevious = $status === 'completed';

                return [
                    'lesson' => $lesson->only('id', 'number', 'title', 'level'),
                    'status' => $status,
                    'unlocked' => $unlocked,
                    'quiz_score' => $item?->quiz_score,
                    'quiz_passed_at' => $item?->quiz_passed_at,
                    'last_saved_at' => $item?->last_saved_at,
                    'submitted_at' => $item?->submitted_at,
                    'submission' => $latestSubmissions->get($lesson->id)?->only('id', 'version', 'status', 'submitted_at'),
                ];
            });

        return Inertia::render('students/show', [
            'student' => $student->only('id', 'name', 'username', 'grade_level', 'section', 'status', 'last_login_at'),
            'lessons' => $lessons,
            'summary' => [
                'levels' => collect(['Basic', 'Moderate', 'Advanced'])->mapWithKeys(fn ($level) => [
                    $level => $lessons->filter(fn ($row) => $row['lesson']['level'] === $level && $row['status'] === 'completed')->count(),
                ]),
                'currentLesson' => ($lessons->first(fn ($row) => $row['status'] !== 'completed'))['lesson']['title'] ?? 'Course complete',
                'quizAverage' => $lessons->pluck('quiz_score')->filter(fn ($score) => $score !== null)->avg(),
                'finalProjectStatus' => $lessons->first(fn ($row) => $row['lesson']['number'] === 15)['status'] ?? 'not_started',
            ],
            'submissions' => $student->submissions()->with(['lesson:id,title,level', 'feedback'])->latest('submitted_at')->get(),
            'activity' => $student->activityLogs()->latest('occurred_at')->take(30)->get(),
        ]);
    }

    public function update(Request $request, User $student)
    {
        $student->update($request->validate(['status' => 'required|in:active,disabled,archived']));

        return back()->with('success', 'Student status updated.');
    }

    public function resetPassword(User $student, StudentAccountService $accounts)
    {
        abort_unless($student->role === 'student', 404);
        $password = $accounts->temporaryPassword();
        DB::transaction(function () use ($student, $password) {
            $student->update([
                'password' => Hash::make($password), 'must_change_password' => true,
                'remember_token' => Str::random(60),
            ]);
            if (config('session.driver') === 'database') {
                DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))
                    ->where('user_id', $student->id)->delete();
            }
        });

        return back()->with('credentials', ['name' => $student->name, 'username' => $student->username, 'password' => $password]);
    }

    public function unlock(Request $request, User $student, Lesson $lesson)
    {
        $progress = StudentLessonProgress::firstOrNew(['user_id' => $student->id, 'lesson_id' => $lesson->id]);
        if (! $progress->exists) {
            $progress->status = 'not_started';
        }
        $progress->is_manually_unlocked = true;
        $progress->save();

        return back()->with('success', "Lesson {$lesson->number} unlocked for {$student->name}.");
    }
}
