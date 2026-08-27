<?php

namespace App\Console\Commands;

use App\Actions\Legacy\AssessLegacyUsers;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('legacy:import-users {--dry-run : Assess the legacy users without writing data}')]
#[Description('Assess legacy users for a Fortify-compatible identity import')]
class LegacyUserImportCommand extends Command
{
    public function __construct(private AssessLegacyUsers $assessLegacyUsers)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if (! $this->option('dry-run')) {
            $this->components->error('Only --dry-run is currently available. No data was changed.');

            return self::FAILURE;
        }

        try {
            $assessment = $this->assessLegacyUsers->handle();

            $this->components->info('Legacy user import dry run');
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
            $this->line("Identity anomalies: {$assessment->identityAnomalies()}");

            return $assessment->isReady() ? self::SUCCESS : self::FAILURE;
        } catch (Throwable) {
            $this->components->error('Unable to assess users from the configured legacy database connection.');

            return self::FAILURE;
        }
    }
}
