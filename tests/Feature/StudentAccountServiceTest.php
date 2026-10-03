<?php

use App\Models\User;
use App\Services\StudentAccountService;

test('temporary passwords contain exactly five lowercase letters', function () {
    $password = app(StudentAccountService::class)->temporaryPassword();
    expect($password)->toMatch('/^[a-z]{5}$/');
});

test('usernames are normalized and made unique', function () {
    User::factory()->create(['username' => 'juan.delacruz']);
    $service = app(StudentAccountService::class);
    expect($service->username(' Juan ', 'Dela Cruz'))->toBe('juan.delacruz2');
});
