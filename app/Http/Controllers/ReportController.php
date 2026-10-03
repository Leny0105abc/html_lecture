<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    private function rows()
    {
        $total = max(Lesson::where('is_published', true)->count(), 1);

        return User::where('role', 'student')->withCount([
            'lessonProgress as completed' => fn ($q) => $q->where('status', 'completed'),
            'submissions as submissions_count',
        ])->orderBy('last_name')->get()->map(fn ($s) => [
            'name' => $s->name, 'username' => $s->username, 'grade' => $s->grade_level, 'section' => $s->section,
            'completed' => $s->completed, 'total' => $total, 'progress' => round($s->completed / $total * 100),
            'submissions' => $s->submissions_count, 'status' => $s->status,
        ]);
    }

    public function index(): Response
    {
        return Inertia::render('reports/index', ['rows' => $this->rows()]);
    }

    public function csv(Request $request): StreamedResponse
    {
        return response()->streamDownload(function () {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Student', 'Username', 'Grade', 'Section', 'Lessons Completed', 'Total Lessons', 'Progress', 'Submissions', 'Status']);
            foreach ($this->rows() as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        }, 'student-progress-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }
}
