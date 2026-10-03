<?php

namespace App\Http\Controllers;

use App\Models\CaseStudy;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CaseStudyController extends Controller
{
    public function index(Request $request): Response
    {
        $lessonsComplete = $request->user()->role === 'teacher' || $request->user()->lessonProgress()->where('status', 'completed')->count() >= Lesson::where('is_published', true)->count();
        $progress = DB::table('case_study_progress')->where('user_id', $request->user()->id)->get()->keyBy('case_study_id');
        $previousComplete = $lessonsComplete;
        $items = CaseStudy::where('is_published', true)->orderBy('number')->get()->map(function ($case) use ($request, $progress, &$previousComplete) {
            $p = $progress->get($case->id);
            $unlocked = $request->user()->role === 'teacher' || $previousComplete;
            $status = $p->status ?? ($unlocked ? 'not_started' : 'locked');
            if ($request->user()->role === 'student') {
                $previousComplete = $status === 'completed';
            }

            return ['id' => $case->id, 'number' => $case->number, 'title' => $case->title, 'scenario' => $case->scenario, 'objectives' => $case->objectives, 'status' => $status, 'unlocked' => $unlocked];
        });

        return Inertia::render('case-studies/index', ['caseStudies' => $items, 'lessonsComplete' => $lessonsComplete]);
    }
}
