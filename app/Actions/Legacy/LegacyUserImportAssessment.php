<?php

namespace App\Actions\Legacy;

final readonly class LegacyUserImportAssessment
{
    public function __construct(
        public int $totalUsers,
        public int $readyUsers,
        public int $compatiblePasswordHashes,
        public int $verifiedEmailStates,
        public int $missingNames,
        public int $invalidEmails,
        public int $missingPasswordHashes,
        public int $incompatiblePasswordHashes,
        public int $unknownLifecycleStatuses,
        public int $duplicateLegacyEmails,
        public int $existingCanonicalEmails,
        public int $invalidLegacyIds,
        public int $existingCanonicalIds,
        public int $invalidEmailVerificationStates,
        public int $invalidLegacyTimestamps,
    ) {}

    public function unverifiedEmailStates(): int
    {
        return $this->totalUsers - $this->verifiedEmailStates - $this->invalidEmailVerificationStates;
    }

    public function identityAnomalies(): int
    {
        return $this->totalUsers - $this->readyUsers;
    }

    public function isReady(): bool
    {
        return $this->identityAnomalies() === 0;
    }
}
