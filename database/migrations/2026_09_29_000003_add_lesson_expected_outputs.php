<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $examples = require config_path('lesson_examples.php');

        foreach ($examples as $number => $example) {
            DB::table('lessons')->where('number', $number)->update([
                'example_html' => $example['html'],
                'example_css' => $example['css'],
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Lesson examples are educational content and do not need destructive rollback.
    }
};
