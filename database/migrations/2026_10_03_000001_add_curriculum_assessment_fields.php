<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->longText('activity_html')->nullable();
            $table->longText('activity_css')->nullable();
            $table->json('guide')->nullable();
            $table->json('quiz')->nullable();
            $table->json('validation_rules')->nullable();
        });

        Schema::table('student_lesson_progress', function (Blueprint $table) {
            $table->json('extra_files')->nullable();
            $table->json('quiz_answers')->nullable();
            $table->unsignedTinyInteger('quiz_score')->nullable();
            $table->timestamp('quiz_passed_at')->nullable();
            $table->timestamp('activity_passed_at')->nullable();
        });

        Schema::table('submissions', function (Blueprint $table) {
            $table->json('extra_files')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('submissions', fn (Blueprint $table) => $table->dropColumn('extra_files'));
        Schema::table('student_lesson_progress', fn (Blueprint $table) => $table->dropColumn([
            'extra_files', 'quiz_answers', 'quiz_score', 'quiz_passed_at', 'activity_passed_at',
        ]));
        Schema::table('lessons', fn (Blueprint $table) => $table->dropColumn([
            'activity_html', 'activity_css', 'guide', 'quiz', 'validation_rules',
        ]));
    }
};
