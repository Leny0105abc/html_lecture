<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lesson extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['objectives' => 'array', 'guide' => 'array', 'quiz' => 'array', 'validation_rules' => 'array', 'auto_complete' => 'boolean', 'is_published' => 'boolean'];
    }

    public function progress(): HasMany
    {
        return $this->hasMany(StudentLessonProgress::class);
    }
}
