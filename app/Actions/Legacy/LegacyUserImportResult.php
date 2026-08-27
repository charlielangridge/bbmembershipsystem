<?php

namespace App\Actions\Legacy;

final readonly class LegacyUserImportResult
{
    public function __construct(
        public int $canonicalUsersBefore,
        public int $importedUsers,
        public int $canonicalUsersAfter,
    ) {}

    public function expectedCanonicalUsers(): int
    {
        return $this->canonicalUsersBefore + $this->importedUsers;
    }

    public function isReconciled(): bool
    {
        return $this->canonicalUsersAfter === $this->expectedCanonicalUsers();
    }
}
