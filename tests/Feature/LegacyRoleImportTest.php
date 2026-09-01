<?php

use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    config([
        'database.connections.legacy' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
    ]);

    DB::purge('legacy');

    Schema::connection('legacy')->create('users', function (Blueprint $table): void {
        $table->unsignedInteger('id')->primary();
    });

    Schema::connection('legacy')->create('roles', function (Blueprint $table): void {
        $table->unsignedInteger('id')->primary();
        $table->string('name');
        $table->string('title')->nullable();
        $table->timestamps();
    });

    Schema::connection('legacy')->create('role_user', function (Blueprint $table): void {
        $table->unsignedInteger('id')->primary();
        $table->unsignedInteger('role_id');
        $table->unsignedInteger('user_id');
        $table->timestamps();
    });
});

it('assesses retained and derived legacy roles without writing either database', function () {
    User::factory()->create(['id' => 41]);
    User::factory()->create(['id' => 42]);
    DB::connection('legacy')->table('users')->insert([
        ['id' => 41],
        ['id' => 42],
    ]);
    DB::connection('legacy')->table('roles')->insert([
        ['id' => 1, 'name' => 'admin', 'title' => 'Admin', 'created_at' => '2020-01-01 00:00:00', 'updated_at' => '2020-01-01 00:00:00'],
        ['id' => 2, 'name' => 'makers', 'title' => 'Makers', 'created_at' => '2020-01-01 00:00:00', 'updated_at' => '2020-01-01 00:00:00'],
        ['id' => 3, 'name' => 'member', 'title' => 'Member', 'created_at' => '2020-01-01 00:00:00', 'updated_at' => '2020-01-01 00:00:00'],
    ]);
    DB::connection('legacy')->table('role_user')->insert([
        ['id' => 1, 'role_id' => 1, 'user_id' => 41, 'created_at' => '2020-01-01 00:00:00', 'updated_at' => '2020-01-01 00:00:00'],
        ['id' => 2, 'role_id' => 2, 'user_id' => 42, 'created_at' => '2020-01-01 00:00:00', 'updated_at' => '2020-01-01 00:00:00'],
        ['id' => 3, 'role_id' => 3, 'user_id' => 41, 'created_at' => '2020-01-01 00:00:00', 'updated_at' => '2020-01-01 00:00:00'],
    ]);
    $legacyRolesBefore = DB::connection('legacy')->table('roles')->orderBy('id')->get();
    $legacyAssignmentsBefore = DB::connection('legacy')->table('role_user')->orderBy('id')->get();

    $this->artisan('legacy:import-roles', ['--dry-run' => true])
        ->expectsOutputToContain('Legacy role import dry run')
        ->expectsOutputToContain('Total legacy roles: 3')
        ->expectsOutputToContain('Retained roles: 2')
        ->expectsOutputToContain('Permission-bearing roles: 1')
        ->expectsOutputToContain('Unprivileged roles: 1')
        ->expectsOutputToContain('Derived member roles: 1')
        ->expectsOutputToContain('Total legacy assignments: 3')
        ->expectsOutputToContain('Retained assignments: 2')
        ->expectsOutputToContain('Derived member assignments: 1')
        ->expectsOutputToContain('Role anomalies: 0')
        ->doesntExpectOutputToContain('makers')
        ->assertSuccessful();

    expect(DB::table('roles')->count())->toBe(0)
        ->and(DB::table('permissions')->count())->toBe(0)
        ->and(DB::table('model_has_roles')->count())->toBe(0)
        ->and(DB::connection('legacy')->table('roles')->orderBy('id')->get())->toEqual($legacyRolesBefore)
        ->and(DB::connection('legacy')->table('role_user')->orderBy('id')->get())->toEqual($legacyAssignmentsBefore);
});

it('blocks role records and assignments that cannot be imported deterministically', function () {
    User::factory()->create(['id' => 41]);
    DB::connection('legacy')->table('users')->insert(['id' => 41]);
    DB::connection('legacy')->table('roles')->insert([
        ['id' => 1, 'name' => 'admin', 'title' => 'Admin', 'created_at' => '2020-01-01 00:00:00', 'updated_at' => '2020-01-01 00:00:00'],
        ['id' => 2, 'name' => ' ADMIN ', 'title' => 'Duplicate', 'created_at' => '2020-01-01 00:00:00', 'updated_at' => '2020-01-01 00:00:00'],
        ['id' => 3, 'name' => '', 'title' => 'Missing', 'created_at' => '2020-01-01 00:00:00', 'updated_at' => '2020-01-01 00:00:00'],
        ['id' => 4, 'name' => 'makers', 'title' => 'Makers', 'created_at' => '0000-00-00 00:00:00', 'updated_at' => '2020-01-01 00:00:00'],
    ]);
    DB::connection('legacy')->table('role_user')->insert([
        ['id' => 1, 'role_id' => 1, 'user_id' => 41, 'created_at' => '2020-01-01 00:00:00', 'updated_at' => '2020-01-01 00:00:00'],
        ['id' => 2, 'role_id' => 1, 'user_id' => 41, 'created_at' => '2020-01-01 00:00:00', 'updated_at' => '2020-01-01 00:00:00'],
        ['id' => 3, 'role_id' => 99, 'user_id' => 41, 'created_at' => '2020-01-01 00:00:00', 'updated_at' => '2020-01-01 00:00:00'],
        ['id' => 4, 'role_id' => 4, 'user_id' => 99, 'created_at' => '2020-01-01 00:00:00', 'updated_at' => '2020-01-01 00:00:00'],
    ]);

    $this->artisan('legacy:import-roles', ['--dry-run' => true])
        ->expectsOutputToContain('Missing role names: 1')
        ->expectsOutputToContain('Duplicate normalized role names: 2')
        ->expectsOutputToContain('Invalid role timestamps: 1')
        ->expectsOutputToContain('Orphan role assignments: 1')
        ->expectsOutputToContain('Assignments for missing legacy users: 1')
        ->expectsOutputToContain('Assignments for missing canonical users: 1')
        ->expectsOutputToContain('Duplicate retained assignments: 2')
        ->expectsOutputToContain('Role anomalies: 8')
        ->doesntExpectOutputToContain('makers')
        ->assertFailed();

    expect(DB::table('roles')->count())->toBe(0)
        ->and(DB::table('permissions')->count())->toBe(0)
        ->and(DB::table('model_has_roles')->count())->toBe(0);
});

it('blocks a legacy role id that cannot be preserved', function () {
    DB::connection('legacy')->table('roles')->insert([
        'id' => 0,
        'name' => 'admin',
        'title' => 'Admin',
        'created_at' => '2020-01-01 00:00:00',
        'updated_at' => '2020-01-01 00:00:00',
    ]);

    $this->artisan('legacy:import-roles', ['--dry-run' => true])
        ->expectsOutputToContain('Invalid legacy role IDs: 1')
        ->expectsOutputToContain('Role anomalies: 1')
        ->assertFailed();

    expect(DB::table('roles')->count())->toBe(0);
});

it('imports retained roles and assignments with only approved role permissions', function () {
    foreach (range(41, 49) as $userId) {
        User::factory()->create(['id' => $userId]);
        DB::connection('legacy')->table('users')->insert(['id' => $userId]);
    }

    DB::connection('legacy')->table('roles')->insert([
        ['id' => 10, 'name' => 'admin', 'title' => 'Admin', 'created_at' => '2020-01-01 00:00:00', 'updated_at' => '2021-01-01 00:00:00'],
        ['id' => 11, 'name' => 'finance', 'title' => 'Finance', 'created_at' => '2020-01-01 00:00:00', 'updated_at' => '2020-01-01 00:00:00'],
        ['id' => 12, 'name' => 'laser', 'title' => 'Laser', 'created_at' => '2020-01-01 00:00:00', 'updated_at' => '2020-01-01 00:00:00'],
        ['id' => 13, 'name' => ' Makers Team ', 'title' => 'Makers', 'created_at' => '2020-01-01 00:00:00', 'updated_at' => '2020-01-01 00:00:00'],
        ['id' => 14, 'name' => 'member', 'title' => 'Member', 'created_at' => '2020-01-01 00:00:00', 'updated_at' => '2020-01-01 00:00:00'],
        ['id' => 15, 'name' => 'storage-box-user', 'title' => 'Storage box user', 'created_at' => '2020-01-01 00:00:00', 'updated_at' => '2020-01-01 00:00:00'],
        ['id' => 16, 'name' => 'comms', 'title' => 'Communications', 'created_at' => '2020-01-01 00:00:00', 'updated_at' => '2020-01-01 00:00:00'],
        ['id' => 17, 'name' => 'equipment', 'title' => 'Equipment', 'created_at' => '2020-01-01 00:00:00', 'updated_at' => '2020-01-01 00:00:00'],
        ['id' => 18, 'name' => 'acs', 'title' => 'Access control', 'created_at' => '2020-01-01 00:00:00', 'updated_at' => '2020-01-01 00:00:00'],
        ['id' => 19, 'name' => 'storage', 'title' => 'Storage', 'created_at' => '2020-01-01 00:00:00', 'updated_at' => '2020-01-01 00:00:00'],
    ]);
    DB::connection('legacy')->table('role_user')->insert([
        ['id' => 1, 'role_id' => 10, 'user_id' => 41, 'created_at' => '2020-01-01 00:00:00', 'updated_at' => '2020-01-01 00:00:00'],
        ['id' => 2, 'role_id' => 11, 'user_id' => 42, 'created_at' => '2020-01-01 00:00:00', 'updated_at' => '2020-01-01 00:00:00'],
        ['id' => 3, 'role_id' => 12, 'user_id' => 43, 'created_at' => '2020-01-01 00:00:00', 'updated_at' => '2020-01-01 00:00:00'],
        ['id' => 4, 'role_id' => 13, 'user_id' => 44, 'created_at' => '2020-01-01 00:00:00', 'updated_at' => '2020-01-01 00:00:00'],
        ['id' => 5, 'role_id' => 14, 'user_id' => 44, 'created_at' => '2020-01-01 00:00:00', 'updated_at' => '2020-01-01 00:00:00'],
        ['id' => 6, 'role_id' => 15, 'user_id' => 45, 'created_at' => '2020-01-01 00:00:00', 'updated_at' => '2020-01-01 00:00:00'],
        ['id' => 7, 'role_id' => 16, 'user_id' => 46, 'created_at' => '2020-01-01 00:00:00', 'updated_at' => '2020-01-01 00:00:00'],
        ['id' => 8, 'role_id' => 17, 'user_id' => 47, 'created_at' => '2020-01-01 00:00:00', 'updated_at' => '2020-01-01 00:00:00'],
        ['id' => 9, 'role_id' => 18, 'user_id' => 48, 'created_at' => '2020-01-01 00:00:00', 'updated_at' => '2020-01-01 00:00:00'],
        ['id' => 10, 'role_id' => 19, 'user_id' => 49, 'created_at' => '2020-01-01 00:00:00', 'updated_at' => '2020-01-01 00:00:00'],
    ]);
    $legacyRolesBefore = DB::connection('legacy')->table('roles')->orderBy('id')->get();
    $legacyAssignmentsBefore = DB::connection('legacy')->table('role_user')->orderBy('id')->get();

    $this->artisan('legacy:import-roles', ['--commit' => true])
        ->expectsOutputToContain('Canonical roles before import: 0')
        ->expectsOutputToContain('Imported roles: 9')
        ->expectsOutputToContain('Canonical roles after import: 9')
        ->expectsOutputToContain('Imported permissions: 9')
        ->expectsOutputToContain('Imported role assignments: 9')
        ->expectsOutputToContain('Imported role permission grants: 9')
        ->expectsOutputToContain('Authorization import reconciled.')
        ->doesntExpectOutputToContain(' Makers Team ')
        ->assertSuccessful();

    $administrator = User::query()->findOrFail(41);
    $financeUser = User::query()->findOrFail(42);
    $laserUser = User::query()->findOrFail(43);
    $unknownRoleUser = User::query()->findOrFail(44);
    $storageBoxUser = User::query()->findOrFail(45);
    $communicationsUser = User::query()->findOrFail(46);
    $equipmentUser = User::query()->findOrFail(47);
    $accessControlUser = User::query()->findOrFail(48);
    $storageUser = User::query()->findOrFail(49);
    $adminRole = Role::findByName('admin', 'web');

    expect(Role::count())->toBe(9)
        ->and(Permission::count())->toBe(9)
        ->and(Role::query()->where('name', 'member')->exists())->toBeFalse()
        ->and($adminRole->getKey())->toBe(10)
        ->and($adminRole->created_at?->toDateTimeString())->toBe('2020-01-01 00:00:00')
        ->and($adminRole->updated_at?->toDateTimeString())->toBe('2021-01-01 00:00:00')
        ->and($administrator->hasRole('admin'))->toBeTrue()
        ->and($administrator->can('roles.manage'))->toBeTrue()
        ->and($administrator->can('members.manage'))->toBeTrue()
        ->and($administrator->can('storage.manage'))->toBeFalse()
        ->and($financeUser->can('admin-panel.access'))->toBeFalse()
        ->and($financeUser->can('finance.manage'))->toBeTrue()
        ->and($financeUser->can('roles.manage'))->toBeFalse()
        ->and($laserUser->hasRole('laser'))->toBeTrue()
        ->and($laserUser->getAllPermissions())->toBeEmpty()
        ->and($unknownRoleUser->hasRole(' Makers Team '))->toBeTrue()
        ->and($unknownRoleUser->getAllPermissions())->toBeEmpty()
        ->and($storageBoxUser->hasRole('storage-box-user'))->toBeTrue()
        ->and($storageBoxUser->getAllPermissions())->toBeEmpty()
        ->and($communicationsUser->can('communications.manage'))->toBeTrue()
        ->and($communicationsUser->can('inductions.manage'))->toBeTrue()
        ->and($communicationsUser->can('admin-panel.access'))->toBeFalse()
        ->and($communicationsUser->can('finance.manage'))->toBeFalse()
        ->and($equipmentUser->can('equipment.manage'))->toBeTrue()
        ->and($equipmentUser->can('access-control.manage'))->toBeFalse()
        ->and($accessControlUser->can('access-control.manage'))->toBeTrue()
        ->and($storageUser->can('storage.manage'))->toBeTrue()
        ->and($administrator->getDirectPermissions())->toBeEmpty()
        ->and(DB::table('model_has_roles')->count())->toBe(9)
        ->and(DB::connection('legacy')->table('roles')->orderBy('id')->get())->toEqual($legacyRolesBefore)
        ->and(DB::connection('legacy')->table('role_user')->orderBy('id')->get())->toEqual($legacyAssignmentsBefore);

    $this->artisan('legacy:import-roles', ['--commit' => true])
        ->expectsOutputToContain('Existing canonical authorization records: 36')
        ->expectsOutputToContain('Import blocked: resolve 36 role anomalies before committing. No data was changed.')
        ->assertFailed();

    expect(Role::count())->toBe(9)
        ->and(DB::table('model_has_roles')->count())->toBe(9);
});

it('rolls back the permission store when imported authorization counts do not reconcile', function () {
    User::factory()->create(['id' => 41]);
    DB::connection('legacy')->table('users')->insert(['id' => 41]);
    DB::connection('legacy')->table('roles')->insert([
        'id' => 10,
        'name' => 'admin',
        'title' => 'Admin',
        'created_at' => '2020-01-01 00:00:00',
        'updated_at' => '2020-01-01 00:00:00',
    ]);
    DB::connection('legacy')->table('role_user')->insert([
        'id' => 1,
        'role_id' => 10,
        'user_id' => 41,
        'created_at' => '2020-01-01 00:00:00',
        'updated_at' => '2020-01-01 00:00:00',
    ]);
    DB::listen(function (QueryExecuted $query): void {
        if ($query->connectionName === config('database.default')
            && preg_match('/insert into [`"]?model_has_roles[`"]?/i', $query->sql) === 1) {
            DB::table('model_has_roles')->delete();
        }
    });

    $this->artisan('legacy:import-roles', ['--commit' => true])
        ->expectsOutputToContain('Unable to import roles into the canonical permission store. No data was changed.')
        ->doesntExpectOutputToContain('did not reconcile')
        ->assertFailed();

    expect(DB::table('roles')->count())->toBe(0)
        ->and(DB::table('permissions')->count())->toBe(0)
        ->and(DB::table('model_has_roles')->count())->toBe(0)
        ->and(DB::table('role_has_permissions')->count())->toBe(0);
});

it('requires exactly one role import execution mode', function () {
    $this->artisan('legacy:import-roles')
        ->expectsOutputToContain('Choose exactly one of --dry-run or --commit. No data was changed.')
        ->assertFailed();

    $this->artisan('legacy:import-roles', [
        '--dry-run' => true,
        '--commit' => true,
    ])
        ->expectsOutputToContain('Choose exactly one of --dry-run or --commit. No data was changed.')
        ->assertFailed();

    expect(DB::table('roles')->count())->toBe(0)
        ->and(DB::table('permissions')->count())->toBe(0);
});
