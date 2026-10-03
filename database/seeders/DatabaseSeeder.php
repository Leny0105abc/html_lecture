<?php

namespace Database\Seeders;

use App\Models\CaseStudy;
use App\Models\User;
use App\Services\StudentAccountService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(['username' => 'teacher'], [
            'name' => 'CodeLab Teacher', 'email' => 'teacher@codelab.local',
            'password' => Hash::make(env('DEV_TEACHER_PASSWORD', 'teacher123')),
            'role' => 'teacher', 'status' => 'active', 'email_verified_at' => now(),
        ]);

        $studentPassword = app(StudentAccountService::class)->temporaryPassword();
        User::updateOrCreate(['username' => 'mia.student'], [
            'name' => 'Mia Student', 'first_name' => 'Mia', 'last_name' => 'Student',
            'email' => 'mia.student@student.local', 'password' => Hash::make($studentPassword),
            'role' => 'student', 'grade_level' => '10', 'section' => 'Ruby',
            'status' => 'active', 'must_change_password' => true, 'email_verified_at' => now(),
        ]);

        $this->call(CurriculumSeeder::class);

        $cases = [
            ['Personal Profile Page', 'A student needs a polished one-page profile to introduce their interests and goals.', ['Semantic profile content', 'Accessible image', 'Contact link']],
            ['School Information Page', 'Your school needs a clear information page for new students and families.', ['Headings and paragraphs', 'Lists and links', 'School image']],
            ['Student Registration Form', 'Create an accessible registration form that collects essential student information.', ['Labels for every field', 'Appropriate input types', 'Readable form styling']],
            ['Responsive Product / Service Page', 'A local service needs a page that works beautifully on phones and desktops.', ['Flexbox or Grid', 'Responsive navigation', 'Media query']],
            ['Final Mini Website', 'Combine everything you learned into a complete responsive three-section website.', ['Semantic structure', 'Responsive layout', 'Consistent visual system']],
        ];
        foreach ($cases as $index => [$title, $scenario, $requirements]) {
            CaseStudy::updateOrCreate(['number' => $index + 1], [
                'title' => $title, 'slug' => Str::slug($title), 'scenario' => $scenario,
                'objectives' => ['Plan a page from a realistic brief', 'Apply HTML and CSS independently', 'Review and improve the result'],
                'requirements' => $requirements, 'instructions' => 'Plan the content, build the semantic HTML, add responsive styling, test the result, then save and submit.',
                'concepts' => ['Semantic HTML', 'CSS layout', 'Accessibility', 'Responsive design'],
                'starter_html' => '<main>\n  <!-- Build your case study here -->\n</main>',
                'starter_css' => '* { box-sizing: border-box; }\nbody { margin: 0; font-family: system-ui, sans-serif; }',
                'expected_features' => $requirements, 'rubric' => ['Requirements' => 40, 'HTML quality' => 20, 'CSS and responsiveness' => 25, 'Accessibility' => 15],
                'is_published' => true,
            ]);
        }

        $this->command?->info('Development teacher: teacher / '.env('DEV_TEACHER_PASSWORD', 'teacher123'));
        $this->command?->info('Development student: mia.student / '.$studentPassword);
    }
}
