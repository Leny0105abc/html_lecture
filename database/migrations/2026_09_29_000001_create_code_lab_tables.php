<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('id');
            $table->string('role')->default('student')->index()->after('password');
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('grade_level')->nullable()->index();
            $table->string('section')->nullable()->index();
            $table->string('lrn')->nullable()->unique();
            $table->string('status')->default('active')->index();
            $table->boolean('must_change_password')->default(false);
            $table->timestamp('last_login_at')->nullable();
        });

        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('number')->unique();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('level')->index();
            $table->unsignedSmallInteger('position')->index();
            $table->json('objectives');
            $table->text('introduction');
            $table->longText('explanation');
            $table->text('syntax')->nullable();
            $table->longText('example_html')->nullable();
            $table->longText('example_css')->nullable();
            $table->text('important_notes')->nullable();
            $table->text('guided_practice');
            $table->text('activity');
            $table->text('expected_result');
            $table->text('challenge')->nullable();
            $table->text('completion_requirements');
            $table->longText('starter_html')->nullable();
            $table->longText('starter_css')->nullable();
            $table->boolean('auto_complete')->default(false);
            $table->boolean('is_published')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('case_studies', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('number')->unique();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('scenario');
            $table->json('objectives');
            $table->json('requirements');
            $table->text('instructions');
            $table->json('concepts');
            $table->longText('starter_html')->nullable();
            $table->longText('starter_css')->nullable();
            $table->json('expected_features');
            $table->json('rubric');
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('student_lesson_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('not_started')->index();
            $table->boolean('is_manually_unlocked')->default(false);
            $table->longText('html_code')->nullable();
            $table->longText('css_code')->nullable();
            $table->longText('js_code')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('last_saved_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'lesson_id']);
            $table->index(['user_id', 'status']);
        });

        Schema::create('case_study_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('case_study_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('not_started')->index();
            $table->longText('html_code')->nullable();
            $table->longText('css_code')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('last_saved_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'case_study_id']);
        });

        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('case_study_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('status')->default('submitted')->index();
            $table->longText('html_code');
            $table->longText('css_code')->nullable();
            $table->longText('js_code')->nullable();
            $table->timestamp('submitted_at');
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'lesson_id', 'case_study_id', 'version'], 'submission_version_unique');
        });

        Schema::create('teacher_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->text('comment');
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('event')->index();
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();
            $table->index(['user_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('teacher_feedback');
        Schema::dropIfExists('submissions');
        Schema::dropIfExists('case_study_progress');
        Schema::dropIfExists('student_lesson_progress');
        Schema::dropIfExists('case_studies');
        Schema::dropIfExists('lessons');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'role', 'first_name', 'middle_name', 'last_name', 'grade_level', 'section', 'lrn', 'status', 'must_change_password', 'last_login_at']);
        });
    }
};
