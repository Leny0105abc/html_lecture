<?php

use App\Models\Lesson;
use App\Models\StudentLessonProgress;
use App\Models\Submission;
use App\Models\User;
use Database\Seeders\CurriculumSeeder;
use Illuminate\Support\Facades\DB;

test('student deletion requires explicit confirmation', function () {
    $teacher = User::factory()->create(['role' => 'teacher']);
    $student = User::factory()->create();
    $this->actingAs($teacher)->delete('/students/'.$student->id)->assertSessionHasErrors('confirmed');
    $this->delete('/students/'.$student->id, ['confirmed' => false])->assertSessionHasErrors('confirmed');
    $this->assertDatabaseHas('users', ['id' => $student->id]);
});

test('only teachers can delete students and teacher accounts are protected', function () {
    $teacher = User::factory()->create(['role' => 'teacher']);
    $student = User::factory()->create();
    $this->delete('/students/'.$student->id, ['confirmed' => true])->assertRedirect('/login');
    $this->actingAs($student)->delete('/students/'.$student->id, ['confirmed' => true])->assertForbidden();
    $this->actingAs($teacher)->delete('/students/'.$teacher->id, ['confirmed' => true])->assertNotFound();
    $this->assertDatabaseHas('users', ['id' => $teacher->id]);
    $this->assertDatabaseHas('users', ['id' => $student->id]);
});

test('confirmed deletion removes only the selected student and dependent records', function () {
    $this->seed(CurriculumSeeder::class);
    config(['session.driver' => 'database']);
    $teacher = User::factory()->create(['role' => 'teacher']);
    $student = User::factory()->create();
    $other = User::factory()->create();
    $lesson = Lesson::first();
    foreach ([$student, $other] as $user) {
        StudentLessonProgress::create(['user_id' => $user->id, 'lesson_id' => $lesson->id]);
        $submission = Submission::create(['user_id' => $user->id, 'lesson_id' => $lesson->id, 'version' => 1, 'html_code' => '<h1>Test</h1>', 'submitted_at' => now()]);
        DB::table('teacher_feedback')->insert(['submission_id' => $submission->id, 'teacher_id' => $teacher->id, 'comment' => 'Good work']);
        DB::table('activity_logs')->insert(['user_id' => $user->id, 'event' => 'submitted', 'occurred_at' => now()]);
        DB::table('sessions')->insert(['id' => 'student-'.$user->id, 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);
        DB::table('password_reset_tokens')->insert(['email' => $user->email, 'token' => 'test-token']);
        DB::table('passkeys')->insert(['user_id' => $user->id, 'name' => 'Test', 'credential_id' => 'test-'.$user->id, 'credential' => '{}']);
    }
    $this->actingAs($teacher)->delete('/students/'.$student->id, ['confirmed' => true])->assertRedirect('/students')->assertSessionHas('success');
    $this->assertDatabaseMissing('users', ['id' => $student->id]);
    foreach (['student_lesson_progress', 'submissions', 'activity_logs', 'sessions', 'passkeys'] as $table) {
        $this->assertDatabaseMissing($table, ['user_id' => $student->id]);
        $this->assertDatabaseHas($table, ['user_id' => $other->id]);
    }
    $this->assertDatabaseMissing('password_reset_tokens', ['email' => $student->email]);
    expect(DB::table('teacher_feedback')->count())->toBe(1);
    $this->assertDatabaseHas('users', ['id' => $teacher->id]);
    $this->assertDatabaseHas('users', ['id' => $other->id]);
    $this->assertDatabaseHas('lessons', ['id' => $lesson->id]);
});
