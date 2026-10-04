<?php

use App\Http\Controllers\AiTutorController;
use App\Http\Controllers\CaseStudyController;
use App\Http\Controllers\CaseStudyLabController;
use App\Http\Controllers\CodeLabController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SubmissionController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('lessons', [LessonController::class, 'index'])->name('lessons.index');
    Route::get('case-studies', [CaseStudyController::class, 'index'])->name('case-studies.index');

    Route::middleware('role:student')->group(function () {
        Route::get('code-lab/{lesson}', [CodeLabController::class, 'show'])->name('code-lab.show');
        Route::put('code-lab/{lesson}/save', [CodeLabController::class, 'save'])->name('code-lab.save');
        Route::post('code-lab/{lesson}/check', [CodeLabController::class, 'check'])->name('code-lab.check');
        Route::post('code-lab/{lesson}/submit', [CodeLabController::class, 'submit'])->name('code-lab.submit');
        Route::post('code-lab/{lesson}/quiz', [CodeLabController::class, 'quiz'])->name('code-lab.quiz');
        Route::post('code-lab/{lesson}/ai', AiTutorController::class)->middleware('throttle:20,1')->name('code-lab.ai');
        Route::get('case-studies/{caseStudy}', [CaseStudyLabController::class, 'show'])->name('case-studies.show');
        Route::put('case-studies/{caseStudy}/save', [CaseStudyLabController::class, 'save'])->name('case-studies.save');
        Route::post('case-studies/{caseStudy}/submit', [CaseStudyLabController::class, 'submit'])->name('case-studies.submit');
    });

    Route::middleware('role:teacher')->group(function () {
        Route::post('lessons', [LessonController::class, 'store'])->name('lessons.store');
        Route::delete('lessons/{lesson}', [LessonController::class, 'destroy'])->name('lessons.destroy');
        Route::resource('students', StudentController::class)->only(['index', 'store', 'show', 'update']);
        Route::post('students/{student}/reset-password', [StudentController::class, 'resetPassword'])->middleware('throttle:10,1')->name('students.reset-password');
        Route::post('students/{student}/unlock/{lesson}', [StudentController::class, 'unlock'])->name('students.unlock');
        Route::get('submissions', [SubmissionController::class, 'index'])->name('submissions.index');
        Route::get('submissions/{submission}', [SubmissionController::class, 'show'])->name('submissions.show');
        Route::post('submissions/{submission}/review', [SubmissionController::class, 'review'])->name('submissions.review');
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/progress.csv', [ReportController::class, 'csv'])->name('reports.csv');
    });
});

require __DIR__.'/settings.php';
