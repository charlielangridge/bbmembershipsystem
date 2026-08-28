<?php

namespace App\Actions\Legacy;

use App\Models\User;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use RuntimeException;
use Spatie\Permission\PermissionRegistrar;
use stdClass;

final class ImportLegacyRoles
{
    public function __construct(
        private DatabaseManager $database,
        private LegacyAuthorizationMatrix $authorizationMatrix,
        private PermissionRegistrar $permissionRegistrar,
    ) {}

    public function handle(): LegacyRoleImportResult
    {
        $canonicalConnection = $this->database->connection();
        $legacyConnection = $this->database->connection('legacy');
        /** @var array{roles: string, permissions: string, model_has_roles: string, model_has_permissions: string, role_has_permissions: string} $tableNames */
        $tableNames = config('permission.table_names');

        $result = $canonicalConnection->transaction(function () use ($canonicalConnection, $legacyConnection, $tableNames): LegacyRoleImportResult {
            $canonicalRolesBefore = $canonicalConnection->table($tableNames['roles'])->count();
            $canonicalPermissionsBefore = $canonicalConnection->table($tableNames['permissions'])->count();
            $canonicalAssignmentsBefore = $canonicalConnection->table($tableNames['model_has_roles'])->count();
            $canonicalRolePermissionsBefore = $canonicalConnection->table($tableNames['role_has_permissions'])->count();
            $timestamp = now()->toDateTimeString();
            $permissions = collect($this->authorizationMatrix->permissions())
                ->map(fn (string $permission): array => [
                    'name' => $permission,
                    'guard_name' => 'web',
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ])
                ->all();

            $canonicalConnection->table($tableNames['permissions'])->insert($permissions);
            $importedRoles = $this->importRoles($canonicalConnection, $legacyConnection, $tableNames['roles']);
            $roleNamesById = $legacyConnection->table('roles')->pluck('name', 'id')
                ->map(fn (mixed $name): string => $this->authorizationMatrix->normalizeRoleName($name));
            $importedAssignments = $this->importAssignments(
                $canonicalConnection,
                $legacyConnection,
                $tableNames['model_has_roles'],
                $roleNamesById,
            );
            $importedRolePermissionGrants = $this->grantRolePermissions($canonicalConnection, $tableNames);

            $result = new LegacyRoleImportResult(
                canonicalRolesBefore: $canonicalRolesBefore,
                canonicalRolesAfter: $canonicalConnection->table($tableNames['roles'])->count(),
                importedRoles: $importedRoles,
                canonicalPermissionsBefore: $canonicalPermissionsBefore,
                canonicalPermissionsAfter: $canonicalConnection->table($tableNames['permissions'])->count(),
                importedPermissions: count($permissions),
                canonicalAssignmentsBefore: $canonicalAssignmentsBefore,
                canonicalAssignmentsAfter: $canonicalConnection->table($tableNames['model_has_roles'])->count(),
                importedAssignments: $importedAssignments,
                canonicalRolePermissionsBefore: $canonicalRolePermissionsBefore,
                canonicalRolePermissionsAfter: $canonicalConnection->table($tableNames['role_has_permissions'])->count(),
                importedRolePermissionGrants: $importedRolePermissionGrants,
            );

            if (! $result->isReconciled()) {
                throw new RuntimeException('The canonical authorization counts did not reconcile after import.');
            }

            return $result;
        }, 3);

        $this->permissionRegistrar->forgetCachedPermissions();

        return $result;
    }

    private function importRoles(Connection $canonicalConnection, Connection $legacyConnection, string $roleTable): int
    {
        $importedRoles = 0;

        $legacyConnection->table('roles')
            ->select(['id', 'name', 'created_at', 'updated_at'])
            ->chunkById(500, function (Collection $legacyRoles) use ($canonicalConnection, $roleTable, &$importedRoles): void {
                $roles = $legacyRoles
                    ->map(function (stdClass $legacyRole): ?array {
                        $roleName = $this->authorizationMatrix->normalizeRoleName($legacyRole->name);

                        if ($this->authorizationMatrix->isDerivedMemberRole($roleName)) {
                            return null;
                        }

                        return [
                            'id' => $legacyRole->id,
                            'name' => $this->authorizationMatrix->retainedRoleName($legacyRole->name),
                            'guard_name' => 'web',
                            'created_at' => $legacyRole->created_at,
                            'updated_at' => $legacyRole->updated_at,
                        ];
                    })
                    ->filter()
                    ->values()
                    ->all();

                if ($roles !== []) {
                    $canonicalConnection->table($roleTable)->insert($roles);
                    $importedRoles += count($roles);
                }
            });

        return $importedRoles;
    }

    /** @param Collection<int|string, string> $roleNamesById */
    private function importAssignments(
        Connection $canonicalConnection,
        Connection $legacyConnection,
        string $assignmentTable,
        Collection $roleNamesById,
    ): int {
        $importedAssignments = 0;
        $userModelType = (new User)->getMorphClass();

        $legacyConnection->table('role_user')
            ->select(['id', 'role_id', 'user_id'])
            ->chunkById(500, function (Collection $legacyAssignments) use ($canonicalConnection, $assignmentTable, $roleNamesById, $userModelType, &$importedAssignments): void {
                $assignments = $legacyAssignments
                    ->filter(fn (stdClass $assignment): bool => ! $this->authorizationMatrix->isDerivedMemberRole(
                        $roleNamesById->get($assignment->role_id),
                    ))
                    ->map(fn (stdClass $assignment): array => [
                        'role_id' => $assignment->role_id,
                        'model_type' => $userModelType,
                        'model_id' => $assignment->user_id,
                    ])
                    ->values()
                    ->all();

                if ($assignments !== []) {
                    $canonicalConnection->table($assignmentTable)->insert($assignments);
                    $importedAssignments += count($assignments);
                }
            });

        return $importedAssignments;
    }

    /**
     * @param  array{roles: string, permissions: string, model_has_roles: string, model_has_permissions: string, role_has_permissions: string}  $tableNames
     */
    private function grantRolePermissions(Connection $canonicalConnection, array $tableNames): int
    {
        $permissionIdsByName = $canonicalConnection->table($tableNames['permissions'])->pluck('id', 'name');
        $grants = [];

        foreach ($canonicalConnection->table($tableNames['roles'])->select(['id', 'name'])->get() as $role) {
            foreach ($this->authorizationMatrix->permissionsFor($role->name) as $permission) {
                $grants[] = [
                    'permission_id' => $permissionIdsByName->get($permission),
                    'role_id' => $role->id,
                ];
            }
        }

        if ($grants !== []) {
            $canonicalConnection->table($tableNames['role_has_permissions'])->insert($grants);
        }

        return count($grants);
    }
}
