<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

it('allows users with panel access permission to enter administration', function () {
    $administrator = assignRoleWithPermissions(
        User::factory()->create(),
        'admin',
        ['admin-panel.access'],
    );

    $response = $this->actingAs($administrator)
        ->get(route('filament.admin.pages.dashboard'))
        ->assertOk();

    $response
        ->assertSee('action="'.route('logout').'"', escape: false)
        ->assertDontSee('action="'.route('filament.admin.auth.logout').'"', escape: false);
});

it('redirects administration guests to the Fortify login page', function () {
    $this->get(route('filament.admin.pages.dashboard'))
        ->assertRedirect(route('login'));

    expect(Route::has('filament.admin.auth.login'))->toBeFalse();
});

it('forbids members without panel access permission', function () {
    $member = User::factory()->create();

    $this->actingAs($member)
        ->get(route('filament.admin.pages.dashboard'))
        ->assertForbidden();
});

it('forbids unverified users even when their role grants panel access', function () {
    $administrator = assignRoleWithPermissions(
        User::factory()->unverified()->create(),
        'admin',
        ['admin-panel.access'],
    );

    $this->actingAs($administrator)
        ->get(route('filament.admin.pages.dashboard'))
        ->assertForbidden();
});
