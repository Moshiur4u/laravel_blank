<?php

use App\Models\User;
use App\Models\UserActivityLog;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::middleware(['web', 'auth'])
        ->put('/_test/activity/{id}', fn() => redirect('/'))
        ->name('test.activity.update');
});

test('successful login and logout are recorded', function () {
    /** @var \Tests\TestCase $this */
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);
    $this->post('/logout');

    expect(UserActivityLog::query()->where('user_id', $user->id)->pluck('event')->all())
        ->toBe(['login', 'logout']);
});

test('successful writes record their subject and omit sensitive inputs', function () {
    /** @var \Tests\TestCase $this */
    $user = User::factory()->create();

    $this->actingAs($user)->put('/_test/activity/42', [
        'name' => 'Changed name',
        'password' => 'must-not-be-logged',
    ]);

    $log = UserActivityLog::query()->sole();

    expect($log->event)->toBe('update')
        ->and($log->subject_type)->toBe('System record')
        ->and($log->subject_id)->toBe('42')
        ->and($log->properties)->toBe(['name' => 'Changed name']);
});

test('invalid write redirects are not recorded', function () {
    /** @var \Tests\TestCase $this */
    Route::middleware(['web', 'auth'])
        ->put('/_test/activity-validation', function () {
            throw Illuminate\Validation\ValidationException::withMessages(['name' => 'Required']);
        })
        ->name('test.activity.validation');

    $this->actingAs(User::factory()->create())
        ->from('/_test/activity-validation')
        ->put('/_test/activity-validation', ['name' => '']);

    expect(UserActivityLog::query()->count())->toBe(0);
});
