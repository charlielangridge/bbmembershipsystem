<?php

use App\Models\User;

it('redirects guests to the login page', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

it('allows authenticated users to visit the dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
});
