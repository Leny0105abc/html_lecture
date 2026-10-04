<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

test('teacher can issue a temporary student password without changing progress', function () {
    $teacher = User::factory()->create(['role' => 'teacher']);
    $student = User::factory()->create(['role' => 'student', 'must_change_password' => false]);
    $oldPassword = $student->password;
    $response = $this->actingAs($teacher)->from('/students/'.$student->id)
        ->post('/students/'.$student->id.'/reset-password');
    $response->assertRedirect('/students/'.$student->id)->assertSessionHas('credentials');
    $password = session('credentials.password');
    expect($password)->toMatch('/^[a-zA-Z0-9]{12}$/');
    expect(Hash::check($password, $student->fresh()->password))->toBeTrue();
    expect($student->fresh()->password)->not->toBe($oldPassword);
    expect($student->fresh()->must_change_password)->toBeTrue();
    $this->get('/students/'.$student->id)->assertInertia(fn (Assert $page) => $page
        ->component('students/show')->where('flash.credentials.password', $password));
});

test('students cannot reset passwords and teachers cannot reset teacher accounts', function () {
    $student = User::factory()->create(['role' => 'student', 'must_change_password' => false]);
    $teacher = User::factory()->create(['role' => 'teacher']);
    $this->actingAs($student)->post('/students/'.$student->id.'/reset-password')->assertForbidden();
    $this->actingAs($teacher)->post('/students/'.$teacher->id.'/reset-password')->assertNotFound();
});

test('reset invalidates the student sessions but preserves teacher sessions', function () {
    config(['session.driver' => 'database']);
    $teacher = User::factory()->create(['role' => 'teacher']);
    $student = User::factory()->create(['role' => 'student']);
    foreach ([$teacher, $student] as $user) {
        DB::table('sessions')->insert(['id' => 'existing-'.$user->id, 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);
    }
    $this->actingAs($teacher)->post('/students/'.$student->id.'/reset-password')->assertRedirect();
    $this->assertDatabaseMissing('sessions', ['id' => 'existing-'.$student->id]);
    $this->assertDatabaseHas('sessions', ['id' => 'existing-'.$teacher->id]);
});

test('temporary password users must change password before accessing lessons', function () {
    $student = User::factory()->create(['role' => 'student', 'must_change_password' => true, 'password' => 'Temporary123']);
    $this->actingAs($student)->get('/dashboard')->assertRedirect('/change-temporary-password');
    $this->get('/lessons')->assertRedirect('/change-temporary-password');
    $this->get('/change-temporary-password')->assertOk();
    $this->put('/settings/password', ['current_password' => 'Temporary123', 'password' => 'Temporary123', 'password_confirmation' => 'Temporary123'])->assertSessionHasErrors('password');
    $this->put('/settings/password', ['current_password' => 'wrong', 'password' => 'Personal456', 'password_confirmation' => 'Personal456'])->assertSessionHasErrors('current_password');
    $this->put('/settings/password', ['current_password' => 'Temporary123', 'password' => 'Personal456', 'password_confirmation' => 'Personal456'])->assertRedirect('/dashboard');
    expect($student->fresh()->must_change_password)->toBeFalse();
    expect(Hash::check('Personal456', $student->fresh()->password))->toBeTrue();
    $this->get('/dashboard')->assertOk();
});
