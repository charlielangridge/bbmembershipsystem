<?php

namespace App\Actions\Legacy;

final readonly class LegacyRoleImportAssessment
{
    public function __construct(
        public int $totalRoles,
        public int $retainedRoles,
        public int $permissionBearingRoles,
        public int $unprivilegedRoles,
        public int $derivedMemberRoles,
        public int $totalAssignments,
        public int $retainedAssignments,
        public int $derivedMemberAssignments,
        public int $invalidLegacyRoleIds,
        public int $missingRoleNames,
        public int $duplicateRoleNames,
        public int $invalidRoleTimestamps,
        public int $orphanRoleAssignments,
        public int $assignmentsForMissingLegacyUsers,
        public int $assignmentsForMissingCanonicalUsers,
        public int $duplicateRetainedAssignments,
        public int $existingCanonicalAuthorizationRecords,
        public int $roleAnomalies,
    ) {}

    public function isReady(): bool
    {
        return $this->roleAnomalies === 0;
    }
}
