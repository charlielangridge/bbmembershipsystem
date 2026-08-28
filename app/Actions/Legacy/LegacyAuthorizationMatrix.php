<?php

namespace App\Actions\Legacy;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

final class LegacyAuthorizationMatrix
{
    /** @var array<string, list<string>> */
    private const array ROLE_PERMISSIONS = [
        'admin' => [
            'admin-panel.access',
            'roles.manage',
            'members.manage',
        ],
        'finance' => ['finance.manage'],
        'comms' => ['communications.manage', 'inductions.manage'],
        'equipment' => ['equipment.manage'],
        'acs' => ['access-control.manage'],
        'storage' => ['storage.manage'],
    ];

    public function normalizeRoleName(mixed $name): string
    {
        return Str::of((string) $name)->trim()->lower()->toString();
    }

    public function retainedRoleName(mixed $name): string
    {
        $normalizedRoleName = $this->normalizeRoleName($name);

        if ($this->isDerivedMemberRole($normalizedRoleName) || isset(self::ROLE_PERMISSIONS[$normalizedRoleName])) {
            return $normalizedRoleName;
        }

        return (string) $name;
    }

    public function isDerivedMemberRole(string $roleName): bool
    {
        return $roleName === 'member';
    }

    public function isPermissionBearing(string $roleName): bool
    {
        return $this->permissionsFor($roleName) !== [];
    }

    public function hasPreservableTimestamps(mixed $createdAt, mixed $updatedAt): bool
    {
        return $this->isValidTimestamp($createdAt) && $this->isValidTimestamp($updatedAt);
    }

    /** @return list<string> */
    public function permissionsFor(string $roleName): array
    {
        return self::ROLE_PERMISSIONS[$this->normalizeRoleName($roleName)] ?? [];
    }

    /** @return list<string> */
    public function permissions(): array
    {
        return array_values(array_unique(array_merge(...array_values(self::ROLE_PERMISSIONS))));
    }

    private function isValidTimestamp(mixed $timestamp): bool
    {
        return $timestamp === null
            || CarbonImmutable::canBeCreatedFromFormat((string) $timestamp, 'Y-m-d H:i:s');
    }
}
