<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

it('allows a member to view their own account', function () {
    $member = User::factory()->create([
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
    ]);

    $this->actingAs($member)
        ->get(route('account.show', $member))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('account/Show')
            ->where('account.id', $member->id)
            ->where('account.name', 'Ada Lovelace')
            ->where('account.email', 'ada@example.com')
            ->where('account.email_verified_at', $member->email_verified_at?->toISOString())
            ->where('account.created_at', $member->created_at?->toISOString())
            ->missing('account.password')
            ->missing('account.remember_token')
            ->missing('account.two_factor_secret')
            ->missing('account.roles')
        );
});

it('redirects guests to login', function () {
    $member = User::factory()->create();

    $this->get(route('account.show', $member))
        ->assertRedirect(route('login'));
});

it('requires members to verify their email', function () {
    $member = User::factory()->unverified()->create();

    $this->actingAs($member)
        ->get(route('account.show', $member))
        ->assertRedirect(route('verification.notice'));
});

it('forbids a member from viewing another account', function () {
    $member = User::factory()->create();
    $otherMember = User::factory()->create();

    $this->actingAs($member)
        ->get(route('account.show', $otherMember))
        ->assertForbidden();
});

it('allows a member manager to view another account', function () {
    $permission = Permission::create([
        'name' => 'members.manage',
        'guard_name' => 'web',
    ]);
    $role = Role::create([
        'name' => 'admin',
        'guard_name' => 'web',
    ]);
    $role->givePermissionTo($permission);

    $memberManager = User::factory()->create();
    $memberManager->assignRole($role);
    $member = User::factory()->create();

    $this->actingAs($memberManager)
        ->get(route('account.show', $member))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('account/Show')
            ->where('account.id', $member->id)
        );
});
