<?php

use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('stores a contact form submission so staff can see it in /admin', function () {
    $response = $this->post('/contact', [
        'name' => 'Jane Ortiz',
        'email' => 'jane@example.com',
        'message' => 'Do you work on transmissions?',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    expect(ContactMessage::count())->toBe(1);

    $message = ContactMessage::first();
    expect($message->name)->toBe('Jane Ortiz')
        ->and($message->email)->toBe('jane@example.com')
        ->and($message->status)->toBe('new');
});

it('rejects an incomplete contact form submission', function () {
    $response = $this->post('/contact', [
        'name' => '',
        'email' => 'not-an-email',
        'message' => '',
    ]);

    $response->assertSessionHasErrors(['name', 'email', 'message']);
    expect(ContactMessage::count())->toBe(0);
});
