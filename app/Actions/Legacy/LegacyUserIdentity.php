<?php

namespace App\Actions\Legacy;

use stdClass;

final readonly class LegacyUserIdentity
{
    public function __construct(
        public mixed $id,
        public string $givenName,
        public string $familyName,
        public string $email,
        public mixed $emailVerified,
        public ?string $password,
        public string $status,
        public ?string $createdAt,
        public ?string $updatedAt,
    ) {}

    public static function fromDatabaseRow(stdClass $row): self
    {
        return new self(
            id: $row->id,
            givenName: (string) $row->given_name,
            familyName: (string) $row->family_name,
            email: (string) $row->email,
            emailVerified: $row->email_verified,
            password: $row->password === null ? null : (string) $row->password,
            status: (string) $row->status,
            createdAt: $row->created_at === null ? null : (string) $row->created_at,
            updatedAt: $row->updated_at === null ? null : (string) $row->updated_at,
        );
    }
}
