<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

it('authorizes users through permissions inherited from roles', function () {
    $permission = Permission::create([
        'name' => 'admin-panel.access',
        'guard_name' => 'web',
    ]);
    $role = Role::create([
        'name' => 'admin',
        'guard_name' => 'web',
    ]);
    $role->givePermissionTo($permission);

    $administrator = User::factory()->create();
    $member = User::factory()->create();
    $administrator->assignRole($role);

    expect($administrator->can('admin-panel.access'))->toBeTrue()
        ->and($member->can('admin-panel.access'))->toBeFalse();
});
