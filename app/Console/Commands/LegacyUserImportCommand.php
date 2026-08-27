<?php

namespace App\Console\Commands;

use App\Actions\Legacy\AssessLegacyUsers;
use App\Actions\Legacy\ImportLegacyUsers;
use App\Actions\Legacy\LegacyUserImportAssessment;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Throwable;

#[Signature('legacy:import-users
    {--dry-run : Assess the legacy users without writing data}
    {--commit : Import assessed legacy users into the canonical database}
    {--verified-at= : ISO 8601 cutover timestamp for verified legacy accounts}')]
#[Description('Assess or import legacy users into Fortify-compatible identities')]
class LegacyUserImportCommand extends Command
{
    public function __construct(
        private AssessLegacyUsers $assessLegacyUsers,
        private ImportLegacyUsers $importLegacyUsers,
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

        $verifiedAtInput = (string) $this->option('verified-at');

        if ($isCommit && ! CarbonImmutable::canBeCreatedFromFormat($verifiedAtInput, DateTimeInterface::ATOM)) {
            $this->components->error('A valid --verified-at ISO 8601 cutover timestamp is required. No data was changed.');

            return self::FAILURE;
        }

        try {
            $assessment = $this->assessLegacyUsers->handle();
        } catch (Throwable) {
            $this->components->error('Unable to assess legacy user import compatibility.');

            return self::FAILURE;
        }

        $this->renderAssessment($assessment, $isDryRun);

        if (! $assessment->isReady()) {
            if ($isCommit) {
                $identityAnomalies = $assessment->identityAnomalies();
                $label = Str::plural('identity anomaly', $identityAnomalies);
                $this->components->error("Import blocked: resolve {$identityAnomalies} {$label} before committing. No data was changed.");
            }

            return self::FAILURE;
        }

        if ($isDryRun) {
            return self::SUCCESS;
        }

        $verifiedAt = CarbonImmutable::createFromFormat(DateTimeInterface::ATOM, $verifiedAtInput)?->utc();

        if (! $verifiedAt instanceof CarbonImmutable) {
            $this->components->error('A valid --verified-at ISO 8601 cutover timestamp is required. No data was changed.');

            return self::FAILURE;
        }

        try {
            $result = $this->importLegacyUsers->handle($verifiedAt);
        } catch (Throwable) {
            $this->components->error('Unable to import users into the canonical database. No data was changed.');

            return self::FAILURE;
        }

        $this->line("Canonical users before import: {$result->canonicalUsersBefore}");
        $this->components->info("Imported legacy users: {$result->importedUsers}");
        $this->line("Canonical users after import: {$result->canonicalUsersAfter}");
        $this->line("Canonical user count reconciled: {$result->expectedCanonicalUsers()}");

        return self::SUCCESS;
    }

    private function renderAssessment(LegacyUserImportAssessment $assessment, bool $isDryRun): void
    {
        $this->components->info($isDryRun ? 'Legacy user import dry run' : 'Legacy user import assessment');
        $this->line("Total legacy users: {$assessment->totalUsers}");
        $this->line("Ready for identity import: {$assessment->readyUsers}");
        $this->line("Compatible password hashes: {$assessment->compatiblePasswordHashes}");
        $this->line("Verified email state: {$assessment->verifiedEmailStates}");
        $this->line("Unverified email state: {$assessment->unverifiedEmailStates()}");
        $this->line("Missing names: {$assessment->missingNames}");
        $this->line("Invalid emails: {$assessment->invalidEmails}");
        $this->line("Missing password hashes: {$assessment->missingPasswordHashes}");
        $this->line("Incompatible password hashes: {$assessment->incompatiblePasswordHashes}");
        $this->line("Unknown lifecycle statuses: {$assessment->unknownLifecycleStatuses}");
        $this->line("Duplicate legacy emails: {$assessment->duplicateLegacyEmails}");
        $this->line("Existing canonical emails: {$assessment->existingCanonicalEmails}");
        $this->line("Invalid legacy IDs: {$assessment->invalidLegacyIds}");
        $this->line("Existing canonical IDs: {$assessment->existingCanonicalIds}");
        $this->line("Invalid email verification states: {$assessment->invalidEmailVerificationStates}");
        $this->line("Invalid legacy timestamps: {$assessment->invalidLegacyTimestamps}");
        $this->line("Identity anomalies: {$assessment->identityAnomalies()}");
    }
}
