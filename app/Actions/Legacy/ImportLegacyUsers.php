<?php

namespace App\Actions\Legacy;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use RuntimeException;
use stdClass;

final class ImportLegacyUsers
{
    public function __construct(
        private DatabaseManager $database,
        private LegacyUserIdentityMapper $identityMapper,
    ) {}

    public function handle(CarbonImmutable $verifiedAt): LegacyUserImportResult
    {
        $canonicalConnection = $this->database->connection();
        $legacyConnection = $this->database->connection('legacy');
        $userTable = (new User)->getTable();

        return $canonicalConnection->transaction(function () use ($canonicalConnection, $legacyConnection, $userTable, $verifiedAt): LegacyUserImportResult {
            $canonicalUsersBefore = $canonicalConnection->table($userTable)->count();
            $importedUsers = 0;

            $legacyConnection->table('users')->select([
                'id',
                'given_name',
                'family_name',
                'email',
                'email_verified',
                'password',
                'status',
                'created_at',
                'updated_at',
            ])->chunkById(500, function (Collection $legacyUsers) use ($canonicalConnection, $userTable, $verifiedAt, &$importedUsers): void {
                $users = $legacyUsers
                    ->map(fn (stdClass $legacyUser): array => $this->identityMapper->canonicalAttributes(
                        LegacyUserIdentity::fromDatabaseRow($legacyUser),
                        $verifiedAt,
                    ))
                    ->all();

                $canonicalConnection->table($userTable)->insert($users);
                $importedUsers += $legacyUsers->count();
            });

            $result = new LegacyUserImportResult(
                canonicalUsersBefore: $canonicalUsersBefore,
                importedUsers: $importedUsers,
                canonicalUsersAfter: $canonicalConnection->table($userTable)->count(),
            );

            if (! $result->isReconciled()) {
                throw new RuntimeException('The canonical user count did not reconcile after import.');
            }

            return $result;
        }, 3);
    }
}
