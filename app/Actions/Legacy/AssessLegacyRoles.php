<?php

namespace App\Actions\Legacy;

use App\Models\User;
use Illuminate\Database\DatabaseManager;

final class AssessLegacyRoles
{
    public function __construct(
        private DatabaseManager $database,
        private LegacyAuthorizationMatrix $authorizationMatrix,
    ) {}

    public function handle(): LegacyRoleImportAssessment
    {
        $legacyConnection = $this->database->connection('legacy');
        $canonicalConnection = $this->database->connection();
        /** @var array{roles: string, permissions: string, model_has_roles: string, model_has_permissions: string, role_has_permissions: string} $tableNames */
        $tableNames = config('permission.table_names');
        $existingCanonicalAuthorizationRecords = collect($tableNames)
            ->sum(fn (string $tableName): int => $canonicalConnection->table($tableName)->count());
        $roleNameCounts = [];

        foreach ($legacyConnection->table('roles')->select(['id', 'name'])->lazyById() as $legacyRole) {
            $roleName = $this->authorizationMatrix->normalizeRoleName($legacyRole->name);
            $roleNameCounts[$roleName] = ($roleNameCounts[$roleName] ?? 0) + 1;
        }

        $roleNamesById = [];
        $roleAnomalyIds = [];
        $totalRoles = 0;
        $retainedRoles = 0;
        $permissionBearingRoles = 0;
        $derivedMemberRoles = 0;
        $invalidLegacyRoleIds = 0;
        $missingRoleNames = 0;
        $duplicateRoleNames = 0;
        $invalidRoleTimestamps = 0;

        foreach ($legacyConnection->table('roles')->select(['id', 'name', 'created_at', 'updated_at'])->lazyById() as $legacyRole) {
            $roleName = $this->authorizationMatrix->normalizeRoleName($legacyRole->name);
            $roleId = (string) $legacyRole->id;
            $roleNamesById[$roleId] = $roleName;
            $totalRoles++;

            if (filter_var($legacyRole->id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
                $invalidLegacyRoleIds++;
                $roleAnomalyIds[$roleId] = true;
            }

            if ($roleName === '') {
                $missingRoleNames++;
                $roleAnomalyIds[$roleId] = true;
            }

            if (($roleNameCounts[$roleName] ?? 0) > 1) {
                $duplicateRoleNames++;
                $roleAnomalyIds[$roleId] = true;
            }

            if (! $this->authorizationMatrix->hasPreservableTimestamps($legacyRole->created_at, $legacyRole->updated_at)) {
                $invalidRoleTimestamps++;
                $roleAnomalyIds[$roleId] = true;
            }

            if ($this->authorizationMatrix->isDerivedMemberRole($roleName)) {
                $derivedMemberRoles++;

                continue;
            }

            $retainedRoles++;

            if ($this->authorizationMatrix->isPermissionBearing($roleName)) {
                $permissionBearingRoles++;
            }
        }

        $totalAssignments = 0;
        $retainedAssignments = 0;
        $derivedMemberAssignments = 0;
        $orphanRoleAssignments = 0;
        $assignmentsForMissingLegacyUsers = 0;
        $assignmentsForMissingCanonicalUsers = 0;
        $assignmentAnomalyIds = [];
        $legacyUserIds = [];
        $canonicalUserIds = [];

        foreach ($legacyConnection->table('users')->select('id')->lazyById() as $legacyUser) {
            $legacyUserIds[(string) $legacyUser->id] = true;
        }

        foreach (User::query()->select('id')->lazyById() as $canonicalUser) {
            $canonicalUserIds[(string) $canonicalUser->id] = true;
        }

        $duplicateAssignmentPairs = [];
        $duplicateRetainedAssignments = 0;

        foreach ($legacyConnection->table('role_user')
            ->select(['role_id', 'user_id'])
            ->selectRaw('COUNT(*) as aggregate')
            ->groupBy('role_id', 'user_id')
            ->havingRaw('COUNT(*) > 1')
            ->get() as $duplicateAssignment) {
            $pair = $duplicateAssignment->role_id.'|'.$duplicateAssignment->user_id;
            $roleName = $roleNamesById[(string) $duplicateAssignment->role_id] ?? null;

            if ($roleName !== null && ! $this->authorizationMatrix->isDerivedMemberRole($roleName)) {
                $duplicateAssignmentPairs[$pair] = true;
                $duplicateRetainedAssignments += (int) $duplicateAssignment->aggregate;
            }
        }

        foreach ($legacyConnection->table('role_user')->select(['id', 'role_id', 'user_id'])->lazyById() as $legacyAssignment) {
            $assignmentId = (string) $legacyAssignment->id;
            $roleId = (string) $legacyAssignment->role_id;
            $userId = (string) $legacyAssignment->user_id;
            $totalAssignments++;
            $roleName = $roleNamesById[$roleId] ?? null;

            if ($roleName !== null && $this->authorizationMatrix->isDerivedMemberRole($roleName)) {
                $derivedMemberAssignments++;
            } else {
                $retainedAssignments++;
            }

            if ($roleName === null) {
                $orphanRoleAssignments++;
                $assignmentAnomalyIds[$assignmentId] = true;
            }

            if (! isset($legacyUserIds[$userId])) {
                $assignmentsForMissingLegacyUsers++;
                $assignmentAnomalyIds[$assignmentId] = true;
            }

            if (! isset($canonicalUserIds[$userId])) {
                $assignmentsForMissingCanonicalUsers++;
                $assignmentAnomalyIds[$assignmentId] = true;
            }

            if (isset($duplicateAssignmentPairs[$legacyAssignment->role_id.'|'.$legacyAssignment->user_id])) {
                $assignmentAnomalyIds[$assignmentId] = true;
            }
        }

        return new LegacyRoleImportAssessment(
            totalRoles: $totalRoles,
            retainedRoles: $retainedRoles,
            permissionBearingRoles: $permissionBearingRoles,
            unprivilegedRoles: $retainedRoles - $permissionBearingRoles,
            derivedMemberRoles: $derivedMemberRoles,
            totalAssignments: $totalAssignments,
            retainedAssignments: $retainedAssignments,
            derivedMemberAssignments: $derivedMemberAssignments,
            invalidLegacyRoleIds: $invalidLegacyRoleIds,
            missingRoleNames: $missingRoleNames,
            duplicateRoleNames: $duplicateRoleNames,
            invalidRoleTimestamps: $invalidRoleTimestamps,
            orphanRoleAssignments: $orphanRoleAssignments,
            assignmentsForMissingLegacyUsers: $assignmentsForMissingLegacyUsers,
            assignmentsForMissingCanonicalUsers: $assignmentsForMissingCanonicalUsers,
            duplicateRetainedAssignments: $duplicateRetainedAssignments,
            existingCanonicalAuthorizationRecords: $existingCanonicalAuthorizationRecords,
            roleAnomalies: count($roleAnomalyIds) + count($assignmentAnomalyIds) + $existingCanonicalAuthorizationRecords,
        );
    }
}
