<?php

namespace App\Actions\Legacy;

final readonly class LegacyRoleImportResult
{
    public function __construct(
        public int $canonicalRolesBefore,
        public int $canonicalRolesAfter,
        public int $importedRoles,
        public int $canonicalPermissionsBefore,
        public int $canonicalPermissionsAfter,
        public int $importedPermissions,
        public int $canonicalAssignmentsBefore,
        public int $canonicalAssignmentsAfter,
        public int $importedAssignments,
        public int $canonicalRolePermissionsBefore,
        public int $canonicalRolePermissionsAfter,
        public int $importedRolePermissionGrants,
    ) {}

    public function isReconciled(): bool
    {
        return $this->canonicalRolesAfter === $this->canonicalRolesBefore + $this->importedRoles
            && $this->canonicalPermissionsAfter === $this->canonicalPermissionsBefore + $this->importedPermissions
            && $this->canonicalAssignmentsAfter === $this->canonicalAssignmentsBefore + $this->importedAssignments
            && $this->canonicalRolePermissionsAfter === $this->canonicalRolePermissionsBefore + $this->importedRolePermissionGrants;
    }
}
