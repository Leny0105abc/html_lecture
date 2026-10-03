<?php

use App\Models\Lesson;
use App\Models\StudentLessonProgress;
use App\Models\Submission;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function makeLesson(int $number): Lesson
{
    return Lesson::create([
        'number' => $number, 'position' => $number, 'title' => "Lesson {$number}", 'slug' => "lesson-{$number}", 'level' => 'Basic',
        'objectives' => ['Learn'], 'introduction' => 'Intro', 'explanation' => 'Explain', 'guided_practice' => 'Practice',
        'activity' => 'Build', 'expected_result' => 'Page', 'completion_requirements' => 'Submit', 'starter_html' => '<h1>Hello</h1>', 'starter_css' => '',
    ]);
}

test('a newly opened activity supplies its version for saving and submitting', function () {
    $student = User::factory()->create(['role' => 'student']);
    $lesson = makeLesson(1);

    $this->actingAs($student)->get("/code-lab/{$lesson->id}")
        ->assertInertia(fn (Assert $page) => $page->where('workspace.version', 1)->etc());

    $code = ['html_code' => '<h1>My activity</h1>', 'css_code' => ''];
    $this->putJson("/code-lab/{$lesson->id}/save", [...$code, 'version' => 1])
        ->assertOk()->assertJsonPath('version', 2);

    $this->postJson("/code-lab/{$lesson->id}/submit", [...$code, 'version' => 1])
        ->assertUnprocessable()->assertJsonValidationErrors('version');
    expect(Submission::where('user_id', $student->id)->count())->toBe(0);

    $this->postJson("/code-lab/{$lesson->id}/submit", [...$code, 'version' => 2])
        ->assertOk()->assertJsonPath('status', 'submitted');
    expect(Submission::where('user_id', $student->id)->first()->html_code)->toBe($code['html_code']);
});

test('students cannot open a lesson before its prerequisite', function () {
    $student = User::factory()->create(['role' => 'student']);
    makeLesson(1);
    $second = makeLesson(2);
    $this->actingAs($student)->get("/code-lab/{$second->id}")->assertForbidden();
});

test('a completed lesson unlocks the next lesson', function () {
    $student = User::factory()->create(['role' => 'student']);
    $first = makeLesson(1);
    $second = makeLesson(2);
    StudentLessonProgress::create(['user_id' => $student->id, 'lesson_id' => $first->id, 'status' => 'completed']);
    $this->actingAs($student)->get("/code-lab/{$second->id}")->assertOk();
});

test('teacher approval unlocks the next lesson and shows a continue link', function () {
    $teacher = User::factory()->create(['role' => 'teacher']);
    $student = User::factory()->create(['role' => 'student']);
    $first = makeLesson(1);
    $second = makeLesson(2);
    StudentLessonProgress::create(['user_id' => $student->id, 'lesson_id' => $first->id, 'status' => 'submitted']);
    $submission = Submission::create([
        'user_id' => $student->id, 'lesson_id' => $first->id, 'version' => 1,
        'status' => 'submitted', 'html_code' => '<h1>Hello</h1>', 'css_code' => '', 'submitted_at' => now(),
    ]);

    $this->actingAs($teacher)->post("/submissions/{$submission->id}/review", [
        'status' => 'completed',
    ])->assertRedirect();

    expect(StudentLessonProgress::where('user_id', $student->id)->where('lesson_id', $first->id)->first()->status)->toBe('completed');
    $this->actingAs($student)->get("/code-lab/{$first->id}")->assertInertia(fn (Assert $page) => $page
        ->component('code-lab/show')
        ->where('workspace.status', 'completed')
        ->where('nextLesson.id', $second->id)
        ->where('nextLesson.unlocked', true)
        ->etc());
    $this->get("/code-lab/{$second->id}")->assertOk();
});

test('teachers can distinguish saved work from submitted work and review the submission', function () {
    $teacher = User::factory()->create(['role' => 'teacher']);
    $student = User::factory()->create(['role' => 'student']);
    $first = makeLesson(1);
    $second = makeLesson(2);
    StudentLessonProgress::create([
        'user_id' => $student->id, 'lesson_id' => $first->id,
        'status' => 'in_progress', 'html_code' => '<h1>Draft</h1>', 'last_saved_at' => now(),
    ]);

    $this->actingAs($teacher)->get("/students/{$student->id}")->assertInertia(fn (Assert $page) => $page
        ->component('students/show')
        ->where('lessons.0.status', 'in_progress')
        ->where('lessons.0.submission', null)
        ->where('lessons.1.unlocked', false)
        ->etc());

    $submission = Submission::create([
        'user_id' => $student->id, 'lesson_id' => $first->id, 'version' => 1,
        'status' => 'submitted', 'html_code' => '<h1>Finished</h1>', 'css_code' => '', 'submitted_at' => now(),
    ]);
    StudentLessonProgress::where('user_id', $student->id)->where('lesson_id', $first->id)
        ->update(['status' => 'submitted', 'submitted_at' => now()]);

    $this->get("/students/{$student->id}")->assertInertia(fn (Assert $page) => $page
        ->where('lessons.0.status', 'submitted')
        ->where('lessons.0.submission.id', $submission->id)
        ->etc());
    $this->get('/submissions')->assertInertia(fn (Assert $page) => $page
        ->where('awaitingReview', 1)
        ->where('submissions.data.0.id', $submission->id)
        ->etc());
    $this->get("/code-lab/{$second->id}")->assertForbidden();
});

test('manual unlocking does not erase an existing lesson status', function () {
    $teacher = User::factory()->create(['role' => 'teacher']);
    $student = User::factory()->create(['role' => 'student']);
    $lesson = makeLesson(1);
    StudentLessonProgress::create(['user_id' => $student->id, 'lesson_id' => $lesson->id, 'status' => 'submitted']);

    $this->actingAs($teacher)->post("/students/{$student->id}/unlock/{$lesson->id}")->assertRedirect();

    $progress = StudentLessonProgress::where('user_id', $student->id)->where('lesson_id', $lesson->id)->first();
    expect($progress->status)->toBe('submitted');
    expect($progress->is_manually_unlocked)->toBeTrue();
});

test('resubmitting an approved lesson does not relock the next lesson', function () {
    $student = User::factory()->create(['role' => 'student']);
    $first = makeLesson(1);
    $second = makeLesson(2);
    StudentLessonProgress::create([
        'user_id' => $student->id, 'lesson_id' => $first->id, 'status' => 'completed',
        'version' => 1, 'completed_at' => now(),
    ]);

    $this->actingAs($student)->postJson("/code-lab/{$first->id}/submit", [
        'html_code' => '<h1>Improved</h1>', 'css_code' => '', 'version' => 1,
    ])->assertOk()->assertJsonPath('status', 'completed');

    expect(StudentLessonProgress::where('user_id', $student->id)->where('lesson_id', $first->id)->first()->status)->toBe('completed');
    $this->get("/code-lab/{$second->id}")->assertOk();
});

test('a student cannot save another students work', function () {
    $owner = User::factory()->create(['role' => 'student']);
    $other = User::factory()->create(['role' => 'student']);
    $lesson = makeLesson(1);
    StudentLessonProgress::create(['user_id' => $owner->id, 'lesson_id' => $lesson->id, 'status' => 'in_progress', 'version' => 1]);
    $this->actingAs($other)->putJson("/code-lab/{$lesson->id}/save", ['html_code' => '<h1>Changed</h1>', 'css_code' => '', 'version' => 1])->assertNotFound();
    expect(StudentLessonProgress::where('user_id', $owner->id)->first()->html_code)->toBeNull();
});
