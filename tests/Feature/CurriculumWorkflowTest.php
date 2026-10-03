<?php

use App\Models\CaseStudy;
use App\Models\Lesson;
use App\Models\StudentLessonProgress;
use App\Models\Submission;
use App\Models\User;
use App\Services\LessonCodeValidator;
use Database\Seeders\CurriculumSeeder;
use Database\Seeders\DatabaseSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(CurriculumSeeder::class);
});

test('a fresh installation seeds its accounts lessons and case studies', function () {
    $this->seed(DatabaseSeeder::class);

    expect(User::where('username', 'teacher')->firstOrFail()->role)->toBe('teacher');
    expect(User::where('username', 'mia.student')->firstOrFail()->role)->toBe('student');
    expect(Lesson::count())->toBe(15);
    expect(CaseStudy::count())->toBe(5);
});

test('the curriculum contains five correctly ordered lessons per level and no CSS in Basic', function () {
    $lessons = Lesson::orderBy('position')->get();

    expect($lessons)->toHaveCount(15);
    expect($lessons->pluck('number')->all())->toBe(range(1, 15));
    expect($lessons->groupBy('level')->map->count()->all())->toBe(['Basic' => 5, 'Moderate' => 5, 'Advanced' => 5]);
    foreach ($lessons as $lesson) {
        expect($lesson->quiz)->toHaveCount(5);
        foreach ($lesson->quiz as $question) {
            expect($question['choices'])->toHaveCount(4);
        }
    }
    foreach ($lessons->take(10) as $lesson) {
        expect($lesson->example_css)->toBe('');
        expect($lesson->activity_css)->toBe('');
        expect($lesson->starter_css)->toBe('');
        expect($lesson->starter_html)->not->toContain('stylesheet');
    }
});

test('updating lesson content preserves lesson IDs and saved student code', function () {
    $student = User::factory()->create(['role' => 'student']);
    $lesson = Lesson::where('number', 2)->firstOrFail();
    StudentLessonProgress::create([
        'user_id' => $student->id, 'lesson_id' => $lesson->id, 'status' => 'in_progress',
        'html_code' => '<html><body>My saved work</body></html>', 'version' => 3,
    ]);

    $this->seed(CurriculumSeeder::class);

    expect(Lesson::where('number', 2)->first()->id)->toBe($lesson->id);
    $saved = StudentLessonProgress::where('user_id', $student->id)->where('lesson_id', $lesson->id)->first();
    expect($saved->html_code)->toBe('<html><body>My saved work</body></html>');
    expect($saved->version)->toBe(3);
});

test('teacher progress shows level counts current lesson quiz average and final project', function () {
    $teacher = User::factory()->create(['role' => 'teacher']);
    $student = User::factory()->create(['role' => 'student']);
    $first = Lesson::where('number', 1)->firstOrFail();
    $second = Lesson::where('number', 2)->firstOrFail();
    StudentLessonProgress::create(['user_id' => $student->id, 'lesson_id' => $first->id, 'status' => 'completed', 'quiz_score' => 5]);
    StudentLessonProgress::create(['user_id' => $student->id, 'lesson_id' => $second->id, 'status' => 'quiz_pending', 'quiz_score' => 3]);

    $this->actingAs($teacher)->get('/dashboard')->assertInertia(fn (Assert $page) => $page
        ->where('students.0.levelProgress.Basic', 1)
        ->where('students.0.levelProgress.Moderate', 0)
        ->where('students.0.currentLesson', 'Basic 2')
        ->where('students.0.quizAverage', 80)
        ->where('students.0.finalProjectStatus', 'not_started')
        ->etc());
    $this->get("/students/{$student->id}")->assertInertia(fn (Assert $page) => $page
        ->where('summary.levels.Basic', 1)
        ->where('summary.quizAverage', 4)
        ->where('lessons.1.status', 'quiz_pending')
        ->etc());
});

test('coding and quiz are both required to unlock the next lesson', function () {
    $student = User::factory()->create(['role' => 'student']);
    $first = Lesson::where('number', 1)->firstOrFail();
    $second = Lesson::where('number', 2)->firstOrFail();
    $html = '<html><head><title>Me</title></head><body bgcolor="pink"><h1>Hello</h1><h2>About</h2><p>First</p><p>Second</p><marquee>Welcome</marquee></body></html>';

    $this->actingAs($student)->get("/code-lab/{$first->id}")->assertInertia(fn (Assert $page) => $page
        ->missing('lesson.quiz.0.answer')
        ->where('workspace.version', 1)
        ->has('lesson.quiz', 5)
        ->etc());
    $this->postJson("/code-lab/{$first->id}/submit", [
        'html_code' => $html, 'css_code' => '', 'version' => 1,
    ])->assertOk()->assertJsonPath('status', 'quiz_pending');
    $this->get("/code-lab/{$second->id}")->assertForbidden();

    $answers = collect($first->quiz)->pluck('answer')->all();
    $wrong = array_map(fn ($answer) => ($answer + 1) % 4, $answers);
    $wrong[0] = $answers[0];
    $wrong[1] = $answers[1];
    $wrong[2] = $answers[2];
    $this->postJson("/code-lab/{$first->id}/quiz", ['answers' => $wrong])
        ->assertOk()->assertJsonPath('score', 3)->assertJsonPath('passed', false);
    $this->get("/code-lab/{$second->id}")->assertForbidden();

    $passing = $answers;
    $passing[0] = ($answers[0] + 1) % 4;
    $this->postJson("/code-lab/{$first->id}/quiz", ['answers' => $passing])
        ->assertOk()->assertJsonPath('score', 4)->assertJsonPath('completed', true);
    expect(StudentLessonProgress::where('user_id', $student->id)->where('lesson_id', $first->id)->first()->status)->toBe('completed');
    $this->get("/code-lab/{$second->id}")->assertOk();
});

test('quiz may be passed before coding without unlocking early', function () {
    $student = User::factory()->create(['role' => 'student']);
    $first = Lesson::where('number', 1)->firstOrFail();
    $second = Lesson::where('number', 2)->firstOrFail();
    $this->actingAs($student)->get("/code-lab/{$first->id}")->assertOk();
    $this->postJson("/code-lab/{$first->id}/quiz", ['answers' => collect($first->quiz)->pluck('answer')->all()])
        ->assertOk()->assertJsonPath('completed', false);
    $this->get("/code-lab/{$second->id}")->assertForbidden();
    $this->postJson("/code-lab/{$first->id}/submit", [
        'html_code' => '<html><head><title>Me</title></head><body bgcolor="pink"><h1>Hello</h1><h2>About</h2><p>First</p><p>Second</p><marquee>Welcome</marquee></body></html>',
        'css_code' => '', 'version' => 1,
    ])->assertOk()->assertJsonPath('status', 'completed');
    $this->get("/code-lab/{$second->id}")->assertOk();
});

test('coding validation gives specific guidance and Basic rejects CSS', function () {
    $validator = app(LessonCodeValidator::class);
    $basic = Lesson::where('number', 1)->firstOrFail();
    $issues = $validator->check($basic, '<html><head><title>Hi</title></head><body><p>Hi</p></body></html>', 'body { color: red; }');
    expect(implode(' ', $issues))->toContain('<h1>', 'bgcolor', 'HTML only');

    $student = User::factory()->create(['role' => 'student']);
    $this->actingAs($student)->get("/code-lab/{$basic->id}")->assertOk();
    $this->postJson("/code-lab/{$basic->id}/submit", [
        'html_code' => '<html><head><title>Hi</title></head><body><p>Hi</p></body></html>',
        'css_code' => '', 'version' => 1,
    ])->assertStatus(422)->assertJsonPath('message', 'Your activity needs a few changes.');
    expect(StudentLessonProgress::where('user_id', $student->id)->where('lesson_id', $basic->id)->first()->status)->toBe('in_progress');
});

test('legacy starter links do not block revised HTML activities reaching the teacher', function () {
    $student = User::factory()->create(['role' => 'student']);
    $teacher = User::factory()->create(['role' => 'teacher']);
    $lesson = Lesson::where('number', 1)->firstOrFail();
    $html = '<html><head><title>Our Classroom</title><link rel="stylesheet" href="styles.css"></head><body bgcolor="yellow"><h1>Our Classroom</h1><h2>Welcome</h2><p>First</p><p>Second</p><marquee>Have a good day!</marquee></body></html>';
    StudentLessonProgress::create([
        'user_id' => $student->id, 'lesson_id' => $lesson->id,
        'html_code' => $html, 'status' => 'in_progress', 'version' => 14,
    ]);
    Submission::create([
        'user_id' => $student->id, 'lesson_id' => $lesson->id,
        'html_code' => '<h1>Old activity</h1>', 'version' => 1,
        'status' => 'needs_revision', 'submitted_at' => now()->subDay(),
    ]);

    $clean = app(LessonCodeValidator::class)->normalizeLegacyHtml($lesson, $html);
    expect($clean)->not->toContain('styles.css')->toContain('Our Classroom');
    $this->actingAs($student)->get("/code-lab/{$lesson->id}")
        ->assertInertia(fn (Assert $page) => $page->where('workspace.html_code', $clean)->etc());
    $this->postJson("/code-lab/{$lesson->id}/submit", ['html_code' => $html, 'version' => 14])
        ->assertOk()->assertJsonPath('status', 'quiz_pending');

    $latest = Submission::where('user_id', $student->id)->latest('version')->first();
    expect($latest->version)->toBe(2);
    expect($latest->html_code)->toBe($clean);
    $this->actingAs($teacher)->get("/students/{$student->id}")
        ->assertInertia(fn (Assert $page) => $page
            ->where('lessons.0.submission.id', $latest->id)
            ->where('lessons.0.submission.version', 2)->etc());

    $advanced = Lesson::where('number', 11)->firstOrFail();
    expect(app(LessonCodeValidator::class)->normalizeLegacyHtml($advanced, $html))->toBe($html);
    expect(app(LessonCodeValidator::class)->check($lesson, $clean.'<style>h1 { color: red; }</style>', ''))
        ->toContain('This lesson uses HTML only. Remove CSS from the CSS editor and HTML page.');
});

test('advanced CSS selectors and four-page files are checked as concepts, not exact text', function () {
    $validator = app(LessonCodeValidator::class);
    $selectors = Lesson::where('number', 12)->firstOrFail();
    $html = '<h1 id="student-heading">My profile</h1><p class="first">Hello</p><p class="second">World</p>';
    $css = 'p { font-size: 18px; } .first { color: blue; } .second { color: green; } #student-heading { text-align: center; }';
    expect($validator->check($selectors, $html, $css))->toBe([]);
    expect(implode(' ', $validator->check($selectors, $html, 'p { color: blue; }')))->toContain('two different CSS class selectors', '#id selector');

    $final = Lesson::where('number', 15)->firstOrFail();
    $issues = $validator->check($final, '<nav><a href="index.html">Home</a></nav>', 'nav { display: grid; gap: 10px; }');
    expect(implode(' ', $issues))->toContain('about.html', 'gallery.html', 'contact.html');
});

test('the activity output examples satisfy their own coding checks', function () {
    $validator = app(LessonCodeValidator::class);
    foreach (Lesson::where('number', '<', 15)->get() as $lesson) {
        expect($validator->check($lesson, $lesson->activity_html, $lesson->activity_css))
            ->toBe([]);
    }
});

test('the final lesson saves and checks four connected HTML files', function () {
    $student = User::factory()->create(['role' => 'student']);
    foreach (Lesson::where('number', '<', 15)->get() as $earlier) {
        StudentLessonProgress::create(['user_id' => $student->id, 'lesson_id' => $earlier->id, 'status' => 'completed']);
    }
    $final = Lesson::where('number', 15)->firstOrFail();
    $nav = '<nav><a href="index.html">Home</a><a href="about.html">About</a><a href="gallery.html">Gallery</a><a href="contact.html">Contact</a></nav>';
    $html = '<!DOCTYPE html><html><head><title>Home</title></head><body>'.$nav.'<h1 id="home">Welcome</h1><p><b>Our site</b></p><div class="grid"><span>Explore</span><img src="photo.jpg" alt="Photo"><ul><li>One</li></ul><table><tr><th>Topic</th></tr><tr><td>HTML</td></tr></table><form><label>Name<input name="name"></label></form><audio controls><source src="music.mp3" type="audio/mpeg"></audio></div></body></html>';
    $files = [
        'about.html' => '<html><head><title>About</title></head><body>'.$nav.'<h1>About</h1></body></html>',
        'gallery.html' => '<html><head><title>Gallery</title></head><body>'.$nav.'<h1>Gallery</h1></body></html>',
        'contact.html' => '<html><head><title>Contact</title></head><body>'.$nav.'<h1>Contact</h1></body></html>',
    ];
    $css = '.grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; padding: 12px; border: 1px solid blue; margin: 10px; } #home { color: navy; }';

    $this->actingAs($student)->get("/code-lab/{$final->id}")->assertOk();
    $this->postJson("/code-lab/{$final->id}/check", [
        'html_code' => $html, 'css_code' => $css, 'extra_files' => $files,
    ])->assertOk()->assertJsonPath('passed', true);
    $this->putJson("/code-lab/{$final->id}/save", [
        'html_code' => $html, 'css_code' => $css, 'extra_files' => $files, 'version' => 1,
    ])->assertOk()->assertJsonPath('version', 2);
    expect(StudentLessonProgress::where('user_id', $student->id)->where('lesson_id', $final->id)->first()->extra_files)->toBe($files);
    $this->postJson("/code-lab/{$final->id}/submit", [
        'html_code' => $html, 'css_code' => $css, 'extra_files' => $files, 'version' => 2,
    ])->assertOk()->assertJsonPath('status', 'quiz_pending');
    $this->postJson("/code-lab/{$final->id}/quiz", [
        'answers' => collect($final->quiz)->pluck('answer')->all(),
    ])->assertOk()->assertJsonPath('completed', true);
    $this->get('/case-studies')->assertInertia(fn (Assert $page) => $page->where('lessonsComplete', true)->etc());
});
