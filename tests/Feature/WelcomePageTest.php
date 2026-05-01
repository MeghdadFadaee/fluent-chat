<?php

use App\Models\User;

test('guests see the branded welcome page', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Fluent Chat')
        ->assertSee('Private team messaging with files built in')
        ->assertSee('Enter workspace')
        ->assertSee('Files in context')
        ->assertSee(route('login'), false);
});

test('authenticated users see the dashboard call to action', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertSee('Open dashboard')
        ->assertSee(route('dashboard'), false);
});

test('favicon assets are branded for fluent chat', function () {
    $svg = file_get_contents(public_path('favicon.svg'));

    expect($svg)
        ->toContain('Fluent Chat')
        ->not->toContain('#FF2D20');

    expect(public_path('favicon.ico'))
        ->toBeFile()
        ->and(filesize(public_path('favicon.ico')))->toBeGreaterThan(1000)
        ->and(public_path('apple-touch-icon.png'))->toBeFile()
        ->and(filesize(public_path('apple-touch-icon.png')))->toBeGreaterThan(1000);
});
