<?php

namespace App\Actions\Legacy;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

final class LegacyUserIdentityMapper
{
    private const array VERIFIED_EMAIL_STATES = [1, '1', true];

    private const array UNVERIFIED_EMAIL_STATES = [0, '0', false];

    public function name(LegacyUserIdentity $legacyUser): string
    {
        return Str::of("{$legacyUser->givenName} {$legacyUser->familyName}")->squish()->toString();
    }

    public function normalizeEmail(mixed $email): string
    {
        return Str::of((string) $email)->trim()->lower()->toString();
    }

    public function email(LegacyUserIdentity $legacyUser): string
    {
        return $this->normalizeEmail($legacyUser->email);
    }

    public function hasVerifiedEmail(LegacyUserIdentity $legacyUser): bool
    {
        return in_array($legacyUser->emailVerified, self::VERIFIED_EMAIL_STATES, true);
    }

    public function hasUnverifiedEmail(LegacyUserIdentity $legacyUser): bool
    {
        return in_array($legacyUser->emailVerified, self::UNVERIFIED_EMAIL_STATES, true);
    }

    public function hasPreservableTimestamps(LegacyUserIdentity $legacyUser): bool
    {
        return $this->isValidTimestamp($legacyUser->createdAt)
            && $this->isValidTimestamp($legacyUser->updatedAt);
    }

    /**
     * @return array{id: mixed, name: string, email: string, email_verified_at: string|null, password: mixed, remember_token: null, created_at: mixed, updated_at: mixed}
     */
    public function canonicalAttributes(LegacyUserIdentity $legacyUser, CarbonImmutable $verifiedAt): array
    {
        return [
            'id' => $legacyUser->id,
            'name' => $this->name($legacyUser),
            'email' => $this->email($legacyUser),
            'email_verified_at' => $this->hasVerifiedEmail($legacyUser) ? $verifiedAt->toDateTimeString() : null,
            'password' => $legacyUser->password,
            'remember_token' => null,
            'created_at' => $legacyUser->createdAt,
            'updated_at' => $legacyUser->updatedAt,
        ];
    }

    private function isValidTimestamp(mixed $timestamp): bool
    {
        return $timestamp === null
            || CarbonImmutable::canBeCreatedFromFormat((string) $timestamp, 'Y-m-d H:i:s');
    }
}
