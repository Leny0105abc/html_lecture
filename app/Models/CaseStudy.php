<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaseStudy extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['objectives' => 'array', 'requirements' => 'array', 'concepts' => 'array', 'expected_features' => 'array', 'rubric' => 'array', 'is_published' => 'boolean'];
    }
}
