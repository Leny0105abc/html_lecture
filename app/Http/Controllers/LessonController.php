<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LessonController extends Controller
{
    public function index(Request $request): Response
    {
        $progress = $request->user()->role === 'student'
            ? $request->user()->lessonProgress()->get()->keyBy('lesson_id')
            : collect();
        $completedPrevious = true;

        $lessons = Lesson::where('is_published', true)->orderBy('position')->get()->map(function (Lesson $lesson) use ($request, $progress, &$completedPrevious) {
            $itemProgress = $progress->get($lesson->id);
            $unlocked = $request->user()->role === 'teacher' || $completedPrevious || (bool) $itemProgress?->is_manually_unlocked;
            $status = $itemProgress?->status ?? ($unlocked ? 'not_started' : 'locked');
            if ($request->user()->role === 'student') {
                $completedPrevious = $status === 'completed';
            }

            return [
                'id' => $lesson->id, 'number' => $lesson->number, 'title' => $lesson->title,
                'level' => $lesson->level, 'status' => $status, 'unlocked' => $unlocked,
                'autoComplete' => $lesson->auto_complete, 'published' => $lesson->is_published,
            ];
        });

        $levelProgress = collect(['Basic', 'Moderate', 'Advanced'])->mapWithKeys(fn ($level) => [
            $level => [
                'completed' => $lessons->where('level', $level)->where('status', 'completed')->count(),
                'total' => $lessons->where('level', $level)->count(),
            ],
        ]);

        return Inertia::render('lessons/index', [
            'lessons' => $lessons, 'levelProgress' => $levelProgress,
            'isTeacher' => $request->user()->role === 'teacher',
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:150', 'level' => 'required|in:Basic,Moderate,Advanced',
            'activity' => 'required|string', 'objectives' => 'required|array|min:1',
        ]);
        $number = Lesson::max('number') + 1;
        Lesson::create($data + [
            'number' => $number, 'position' => $number, 'slug' => \Str::slug($data['title']).'-'.$number,
            'introduction' => $data['activity'], 'explanation' => $data['activity'], 'guided_practice' => $data['activity'],
            'expected_result' => 'A working webpage that meets the activity requirements.',
            'completion_requirements' => 'Save and submit a working solution for teacher review.',
        ]);

        return back()->with('success', 'Lesson created.');
    }

    public function destroy(Lesson $lesson)
    {
        $lesson->update(['is_published' => false]);

        return back()->with('success', 'Lesson archived.');
    }
}
