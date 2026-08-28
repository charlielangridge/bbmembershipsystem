<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(LazilyRefreshDatabase::class)
    ->in('Feature');

/**
 * Assign a role and its permissions without granting permissions directly to a user.
 *
 * @param  list<string>  $permissionNames
 */
function assignRoleWithPermissions(User $user, string $roleName, array $permissionNames): User
{
    $role = Role::findOrCreate($roleName, 'web');

    foreach ($permissionNames as $permissionName) {
        $role->givePermissionTo(Permission::findOrCreate($permissionName, 'web'));
    }

    $user->assignRole($role);

    return $user;
}
