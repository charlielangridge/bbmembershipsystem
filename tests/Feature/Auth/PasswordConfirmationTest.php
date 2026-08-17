<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('renders the password confirmation screen', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('password.confirm'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('auth/ConfirmPassword'));
});

it('requires authentication for password confirmation', function () {
    $this->get(route('password.confirm'))->assertRedirect(route('login'));
});
