<?php

use App\Enums\UserRole;
use App\Livewire\Auth\CustomerLogin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('logs a customer in with correct credentials', function () {
    User::factory()->create([
        'email' => 'jane@example.com',
        'password' => Hash::make('correct-password'),
        'role' => UserRole::Customer,
    ]);

    Livewire::test(CustomerLogin::class)
        ->set('email', 'jane@example.com')
        ->set('password', 'correct-password')
        ->call('login')
        ->assertRedirect(route('portal.dashboard'));

    expect(auth()->check())->toBeTrue();
});

it('throttles repeated failed login attempts for the same email+IP', function () {
    User::factory()->create([
        'email' => 'jane@example.com',
        'password' => Hash::make('correct-password'),
        'role' => UserRole::Customer,
    ]);

    // 5 wrong attempts should all just report bad credentials...
    for ($i = 0; $i < 5; $i++) {
        Livewire::test(CustomerLogin::class)
            ->set('email', 'jane@example.com')
            ->set('password', 'wrong-password')
            ->call('login')
            ->assertHasErrors('email');
    }

    // ...but the 6th, even with the CORRECT password, should be blocked by the
    // throttle rather than being allowed to keep guessing.
    Livewire::test(CustomerLogin::class)
        ->set('email', 'jane@example.com')
        ->set('password', 'correct-password')
        ->call('login')
        ->assertHasErrors('email');

    expect(auth()->check())->toBeFalse();
});
