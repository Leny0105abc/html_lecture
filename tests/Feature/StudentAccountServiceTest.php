<?php

use App\Models\User;
use App\Services\StudentAccountService;

test('temporary passwords are twelve random alphanumeric characters', function () {
    $password = app(StudentAccountService::class)->temporaryPassword();
    expect($password)->toMatch('/^[a-zA-Z0-9]{12}$/');
});

test('usernames are normalized and made unique', function () {
    User::factory()->create(['username' => 'juan.delacruz']);
    $service = app(StudentAccountService::class);
    expect($service->username(' Juan ', 'Dela Cruz'))->toBe('juan.delacruz2');
});
