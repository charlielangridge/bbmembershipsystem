<?php

namespace App\Actions\Legacy;

use Illuminate\Database\DatabaseManager;
use Illuminate\Hashing\BcryptHasher;
use Illuminate\Hashing\HashManager;
use Illuminate\Support\Str;
use LogicException;

final class AssessLegacyUsers
{
    private const array KNOWN_LIFECYCLE_STATUSES = [
        'setting-up',
        'active',
        'payment-warning',
        'suspended',
        'leaving',
        'on-hold',
        'left',
        'honorary',
    ];

    private const array VERIFIED_EMAIL_STATES = [1, '1', true];

    private const array UNVERIFIED_EMAIL_STATES = [0, '0', false];

    public function __construct(
        private DatabaseManager $database,
        private HashManager $hash,
    ) {}

    public function handle(): LegacyUserImportAssessment
    {
        $bcryptHasher = $this->hash->driver('bcrypt');

        if (! $bcryptHasher instanceof BcryptHasher) {
            throw new LogicException('The bcrypt hashing driver is not configured correctly.');
        }

        $legacyEmailCounts = [];

        foreach ($this->database->connection('legacy')->table('users')->select(['id', 'email'])->lazyById() as $legacyUser) {
            $normalizedEmail = $this->normalizeEmail($legacyUser->email);
            $legacyEmailCounts[$normalizedEmail] = ($legacyEmailCounts[$normalizedEmail] ?? 0) + 1;
        }

        $canonicalEmails = [];
        $canonicalIds = [];

        foreach ($this->database->table('users')->select(['id', 'email'])->lazyById() as $canonicalUser) {
            $canonicalEmails[$this->normalizeEmail($canonicalUser->email)] = true;
            $canonicalIds[(string) $canonicalUser->id] = true;
        }

        $totalUsers = 0;
        $readyUsers = 0;
        $compatiblePasswordHashes = 0;
        $verifiedEmailStates = 0;
        $missingNames = 0;
        $invalidEmails = 0;
        $missingPasswordHashes = 0;
        $incompatiblePasswordHashes = 0;
        $unknownLifecycleStatuses = 0;
        $duplicateLegacyEmails = 0;
        $existingCanonicalEmails = 0;
        $invalidLegacyIds = 0;
        $existingCanonicalIds = 0;
        $invalidEmailVerificationStates = 0;

        foreach ($this->database->connection('legacy')->table('users')->select([
            'id',
            'given_name',
            'family_name',
            'email',
            'email_verified',
            'password',
            'status',
        ])->lazyById() as $legacyUser) {
            $totalUsers++;
            $hasAnomaly = false;

            if (filter_var($legacyUser->id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
                $invalidLegacyIds++;
                $hasAnomaly = true;
            } elseif (isset($canonicalIds[(string) $legacyUser->id])) {
                $existingCanonicalIds++;
                $hasAnomaly = true;
            }

            if (Str::of("{$legacyUser->given_name} {$legacyUser->family_name}")->squish()->isEmpty()) {
                $missingNames++;
                $hasAnomaly = true;
            }

            $normalizedEmail = $this->normalizeEmail($legacyUser->email);

            if (filter_var($normalizedEmail, FILTER_VALIDATE_EMAIL) === false) {
                $invalidEmails++;
                $hasAnomaly = true;
            } else {
                if ($legacyEmailCounts[$normalizedEmail] > 1) {
                    $duplicateLegacyEmails++;
                    $hasAnomaly = true;
                }

                if (isset($canonicalEmails[$normalizedEmail])) {
                    $existingCanonicalEmails++;
                    $hasAnomaly = true;
                }
            }

            if (blank($legacyUser->password)) {
                $missingPasswordHashes++;
                $hasAnomaly = true;
            } elseif ($bcryptHasher->verifyConfiguration($legacyUser->password)) {
                $compatiblePasswordHashes++;
            } else {
                $incompatiblePasswordHashes++;
                $hasAnomaly = true;
            }

            if (! in_array($legacyUser->status, self::KNOWN_LIFECYCLE_STATUSES, true)) {
                $unknownLifecycleStatuses++;
                $hasAnomaly = true;
            }

            if (in_array($legacyUser->email_verified, self::VERIFIED_EMAIL_STATES, true)) {
                $verifiedEmailStates++;
            } elseif (! in_array($legacyUser->email_verified, self::UNVERIFIED_EMAIL_STATES, true)) {
                $invalidEmailVerificationStates++;
                $hasAnomaly = true;
            }

            if (! $hasAnomaly) {
                $readyUsers++;
            }
        }

        return new LegacyUserImportAssessment(
            totalUsers: $totalUsers,
            readyUsers: $readyUsers,
            compatiblePasswordHashes: $compatiblePasswordHashes,
            verifiedEmailStates: $verifiedEmailStates,
            missingNames: $missingNames,
            invalidEmails: $invalidEmails,
            missingPasswordHashes: $missingPasswordHashes,
            incompatiblePasswordHashes: $incompatiblePasswordHashes,
            unknownLifecycleStatuses: $unknownLifecycleStatuses,
            duplicateLegacyEmails: $duplicateLegacyEmails,
            existingCanonicalEmails: $existingCanonicalEmails,
            invalidLegacyIds: $invalidLegacyIds,
            existingCanonicalIds: $existingCanonicalIds,
            invalidEmailVerificationStates: $invalidEmailVerificationStates,
        );
    }

    private function normalizeEmail(mixed $email): string
    {
        return Str::of((string) $email)->trim()->lower()->toString();
    }
}
