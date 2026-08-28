<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

it('allows member managers to browse member accounts', function () {
    $administrator = assignRoleWithPermissions(
        User::factory()->create(),
        'admin',
        ['admin-panel.access', 'members.manage'],
    );
    $member = User::factory()->create([
        'name' => 'Grace Hopper',
        'email' => 'grace@example.com',
    ]);

    $this->actingAs($administrator)
        ->get(route('filament.admin.resources.users.index'))
        ->assertOk()
        ->assertSee('Grace Hopper')
        ->assertSee('grace@example.com');
});

it('forbids panel users without member management permission from browsing accounts', function () {
    $administrator = assignRoleWithPermissions(
        User::factory()->create(),
        'admin',
        ['admin-panel.access'],
    );

    $this->actingAs($administrator)
        ->get(route('filament.admin.resources.users.index'))
        ->assertForbidden();
});

it('forbids panel users without member management permission from viewing their own admin record', function () {
    $administrator = assignRoleWithPermissions(
        User::factory()->create(),
        'admin',
        ['admin-panel.access'],
    );

    $this->actingAs($administrator)
        ->get(route('filament.admin.resources.users.view', $administrator))
        ->assertForbidden();
});

it('allows member managers to view an account without exposing mutation routes', function () {
    $administrator = assignRoleWithPermissions(
        User::factory()->create(),
        'admin',
        ['admin-panel.access', 'members.manage'],
    );
    $member = User::factory()->create([
        'name' => 'Katherine Johnson',
        'email' => 'katherine@example.com',
    ]);

    $this->actingAs($administrator)
        ->get(route('filament.admin.resources.users.view', $member))
        ->assertOk()
        ->assertSee('Katherine Johnson')
        ->assertSee('katherine@example.com');

    expect(Route::has('filament.admin.resources.users.create'))->toBeFalse()
        ->and(Route::has('filament.admin.resources.users.edit'))->toBeFalse();
});
