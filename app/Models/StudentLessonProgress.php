<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentLessonProgress extends Model
{
    protected $table = 'student_lesson_progress';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'is_manually_unlocked' => 'boolean', 'started_at' => 'datetime', 'last_saved_at' => 'datetime',
            'submitted_at' => 'datetime', 'completed_at' => 'datetime', 'activity_passed_at' => 'datetime',
            'quiz_passed_at' => 'datetime', 'quiz_answers' => 'array', 'extra_files' => 'array',
        ];
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
