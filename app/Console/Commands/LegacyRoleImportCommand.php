<?php

namespace App\Console\Commands;

use App\Actions\Legacy\AssessLegacyRoles;
use App\Actions\Legacy\ImportLegacyRoles;
use App\Actions\Legacy\LegacyRoleImportAssessment;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Throwable;

#[Signature('legacy:import-roles
    {--dry-run : Assess legacy roles and assignments without writing data}
    {--commit : Import assessed roles and assignments into the canonical database}')]
#[Description('Assess or import legacy roles into the canonical permission store')]
class LegacyRoleImportCommand extends Command
{
    public function __construct(
        private AssessLegacyRoles $assessLegacyRoles,
        private ImportLegacyRoles $importLegacyRoles,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $isCommit = (bool) $this->option('commit');

        if ($isDryRun === $isCommit) {
            $this->components->error('Choose exactly one of --dry-run or --commit. No data was changed.');

            return self::FAILURE;
        }

        try {
            $assessment = $this->assessLegacyRoles->handle();
        } catch (Throwable) {
            $this->components->error('Unable to assess legacy role import compatibility.');

            return self::FAILURE;
        }

        $this->renderAssessment($assessment, $isDryRun);

        if (! $assessment->isReady()) {
            if ($isCommit) {
                $label = Str::plural('role anomaly', $assessment->roleAnomalies);
                $this->components->error("Import blocked: resolve {$assessment->roleAnomalies} {$label} before committing. No data was changed.");
            }

            return self::FAILURE;
        }

        if ($isDryRun) {
            return self::SUCCESS;
        }

        try {
            $result = $this->importLegacyRoles->handle();
        } catch (Throwable) {
            $this->components->error('Unable to import roles into the canonical permission store. No data was changed.');

            return self::FAILURE;
        }

        $this->line("Canonical roles before import: {$result->canonicalRolesBefore}");
        $this->components->info("Imported roles: {$result->importedRoles}");
        $this->line("Canonical roles after import: {$result->canonicalRolesAfter}");
        $this->line("Imported permissions: {$result->importedPermissions}");
        $this->line("Imported role assignments: {$result->importedAssignments}");
        $this->line("Imported role permission grants: {$result->importedRolePermissionGrants}");
        $this->components->info('Authorization import reconciled.');

        return self::SUCCESS;
    }

    private function renderAssessment(LegacyRoleImportAssessment $assessment, bool $isDryRun): void
    {
        $this->components->info($isDryRun ? 'Legacy role import dry run' : 'Legacy role import assessment');
        $this->line("Total legacy roles: {$assessment->totalRoles}");
        $this->line("Retained roles: {$assessment->retainedRoles}");
        $this->line("Permission-bearing roles: {$assessment->permissionBearingRoles}");
        $this->line("Unprivileged roles: {$assessment->unprivilegedRoles}");
        $this->line("Derived member roles: {$assessment->derivedMemberRoles}");
        $this->line("Total legacy assignments: {$assessment->totalAssignments}");
        $this->line("Retained assignments: {$assessment->retainedAssignments}");
        $this->line("Derived member assignments: {$assessment->derivedMemberAssignments}");
        $this->line("Invalid legacy role IDs: {$assessment->invalidLegacyRoleIds}");
        $this->line("Missing role names: {$assessment->missingRoleNames}");
        $this->line("Duplicate normalized role names: {$assessment->duplicateRoleNames}");
        $this->line("Invalid role timestamps: {$assessment->invalidRoleTimestamps}");
        $this->line("Orphan role assignments: {$assessment->orphanRoleAssignments}");
        $this->line("Assignments for missing legacy users: {$assessment->assignmentsForMissingLegacyUsers}");
        $this->line("Assignments for missing canonical users: {$assessment->assignmentsForMissingCanonicalUsers}");
        $this->line("Duplicate retained assignments: {$assessment->duplicateRetainedAssignments}");
        $this->line("Existing canonical authorization records: {$assessment->existingCanonicalAuthorizationRecords}");
        $this->line("Role anomalies: {$assessment->roleAnomalies}");
    }
}
