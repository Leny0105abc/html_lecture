<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('lessons')->orderBy('number')->get()->each(function ($lesson): void {
            $title = htmlspecialchars($lesson->title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $html = "<!DOCTYPE html>\n<html>\n  <head>\n    <title>{$title}</title>\n    <link rel=\"stylesheet\" href=\"styles.css\">\n  </head>\n  <body>\n    <h1 class=\"title\">{$title}</h1>\n    <p>Replace this text with your activity.</p>\n  </body>\n</html>";
            $css = "body {\n  padding: 25px;\n}\n\n.title {\n  color: #5C6AC4;\n}";

            DB::table('lessons')->where('id', $lesson->id)->update([
                'starter_html' => $html,
                'starter_css' => $css,
                'updated_at' => now(),
            ]);

            DB::table('student_lesson_progress')
                ->where('lesson_id', $lesson->id)
                ->whereIn('status', ['not_started', 'in_progress'])
                ->where(function ($query): void {
                    $query->whereNull('last_saved_at')->orWhere('version', 1);
                })
                ->update([
                    'html_code' => $html,
                    'css_code' => $css,
                    'updated_at' => now(),
                ]);
        });
    }

    public function down(): void
    {
        // Existing student work is intentionally preserved on rollback.
    }
};
