<?php

namespace Database\Seeders;

use App\Models\Lesson;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CurriculumSeeder extends Seeder
{
    public function run(): void
    {
        $curriculum = require config_path('curriculum.php');

        foreach ($curriculum as $number => $content) {
            $isHtmlOnly = $number <= 10;
            $title = $content['title'];
            $starterHtml = "<!DOCTYPE html>\n<html lang=\"en\">\n<head>\n  <meta charset=\"UTF-8\">\n  <title>My Activity</title>\n".
                ($isHtmlOnly ? '' : "  <link rel=\"stylesheet\" href=\"styles.css\">\n").
                "</head>\n<body>\n  <!-- Write your activity code here -->\n</body>\n</html>";
            $exampleHtml = $content['example_html'];

            Lesson::updateOrCreate(['number' => $number], [
                'position' => $number,
                'title' => $title,
                'slug' => 'lesson-'.$number.'-'.Str::slug($title),
                'level' => $content['level'],
                'objectives' => $content['objectives'],
                'introduction' => $content['introduction'],
                'explanation' => $content['explanation'],
                'syntax' => $content['syntax'],
                'example_html' => $exampleHtml,
                'example_css' => $content['example_css'],
                'activity_html' => $content['activity_html'],
                'activity_css' => $content['activity_css'],
                'important_notes' => $isHtmlOnly
                    ? 'This lesson uses HTML only. Type your own code; the CSS editor begins in the Advanced level.'
                    : 'Keep HTML for content and CSS for design. Test your work at different screen sizes.',
                'guided_practice' => $content['guided_practice'],
                'activity' => $content['activity'],
                'expected_result' => $content['expected_result'],
                'challenge' => $content['challenge'],
                'completion_requirements' => 'Pass the coding checks and score at least 4/5 on the quiz to unlock the next lesson.',
                'guide' => [
                    'steps' => [$content['guided_practice'], 'Run your page and compare it with the Activity output.', 'Submit your code, then pass the quiz.'],
                    'focus' => [['code' => $content['syntax'], 'meaning' => 'Use this syntax as a hint; write your own activity content.']],
                    'html_hint' => $exampleHtml,
                    'css_hint' => $content['example_css'] ?: 'This lesson uses HTML only.',
                    'tip' => $content['challenge'],
                ],
                'quiz' => array_map(fn (array $question) => [
                    'question' => $question[0], 'choices' => $question[1], 'answer' => $question[2], 'explanation' => $question[3],
                ], $content['quiz']),
                'validation_rules' => $content['validation_rules'],
                'starter_html' => $starterHtml,
                'starter_css' => '',
                'auto_complete' => false,
                'is_published' => true,
            ]);
        }
    }
}
