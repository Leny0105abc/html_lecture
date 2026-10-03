<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AiTutorController extends Controller
{
    public function __invoke(Request $request, Lesson $lesson)
    {
        $data = $request->validate(['question' => 'required|string|max:2000', 'html' => 'nullable|string|max:50000', 'css' => 'nullable|string|max:50000']);
        if (! config('services.openai.key')) {
            return response()->json(['message' => 'AI tutoring is not configured yet. Ask your teacher to add an AI API key.'], 503);
        }

        $response = Http::withToken(config('services.openai.key'))->timeout(30)->post('https://api.openai.com/v1/responses', [
            'model' => config('services.openai.model', 'gpt-4.1-mini'),
            'instructions' => 'You are a patient HTML/CSS tutor. Explain first, then give a hint and debugging steps. Never give a full graded solution. Keep answers under 220 words.',
            'input' => "Lesson: {$lesson->title}\nActivity: {$lesson->activity}\nQuestion: {$data['question']}\nHTML:\n".($data['html'] ?? '')."\nCSS:\n".($data['css'] ?? ''),
        ])->throw()->json();

        return response()->json(['message' => data_get($response, 'output.0.content.0.text', 'The tutor could not produce an answer. Try again.')]);
    }
}
