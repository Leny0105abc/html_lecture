<?php

use App\Http\Controllers\AiTutorController;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

test('offline configuration disables AI even when a key is present', function () {
    $environment = Env::getRepository();
    $previousOffline = $environment->get('LAB_OFFLINE');
    $previousKey = $environment->get('OPENAI_API_KEY');

    try {
        $environment->set('LAB_OFFLINE', 'true');
        $environment->set('OPENAI_API_KEY', 'test-key');
        $services = require config_path('services.php');
        expect($services['openai']['key'])->toBeNull();
        config(['services.openai.key' => $services['openai']['key']]);
        Http::preventStrayRequests();

        $request = Request::create('/code-lab/1/ai', 'POST', ['question' => 'How do headings work?']);
        $response = app(AiTutorController::class)($request, new Lesson);
        expect($response->getStatusCode())->toBe(503);
        Http::assertNothingSent();
    } finally {
        $previousOffline === null ? $environment->clear('LAB_OFFLINE') : $environment->set('LAB_OFFLINE', $previousOffline);
        $previousKey === null ? $environment->clear('OPENAI_API_KEY') : $environment->set('OPENAI_API_KEY', $previousKey);
    }
});

test('offline production passwords keep complexity checks without an internet lookup', function () {
    $previousEnvironment = $this->app['env'];

    try {
        $this->app['env'] = 'production';
        config(['lab.offline' => true]);
        Http::preventStrayRequests();

        expect(Validator::make(['password' => 'LabPassword!2026'], ['password' => Password::defaults()])->passes())->toBeTrue();
        expect(Validator::make(['password' => 'short'], ['password' => Password::defaults()])->fails())->toBeTrue();
        expect(Validator::make(['password' => 'alllowercaseletters'], ['password' => Password::defaults()])->fails())->toBeTrue();
        Http::assertNothingSent();
    } finally {
        $this->app['env'] = $previousEnvironment;
    }
});
