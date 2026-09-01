<?php

use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function legacyUserImportRow(int $id, array $overrides = []): array
{
    static $compatibleHash;

    return [
        'id' => $id,
        'given_name' => 'Synthetic',
        'family_name' => 'Member',
        'email' => "member-{$id}@example.test",
        'email_verified' => true,
        'password' => $compatibleHash ??= password_hash('legacy-secret', PASSWORD_BCRYPT, ['cost' => 10]),
        'remember_token' => null,
        'status' => 'active',
        'active' => true,
        'created_at' => '2020-01-01 00:00:00',
        'updated_at' => '2020-01-01 00:00:00',
        ...$overrides,
    ];
}

beforeEach(function () {
    config([
        'hashing.bcrypt.rounds' => 12,
        'database.connections.legacy' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
    ]);

    DB::purge('legacy');

    Schema::connection('legacy')->create('users', function (Blueprint $table): void {
        $table->unsignedInteger('id')->primary();
        $table->string('given_name');
        $table->string('family_name');
        $table->string('email');
        $table->boolean('email_verified');
        $table->string('password')->nullable();
        $table->string('remember_token')->nullable();
        $table->string('status');
        $table->boolean('active');
        $table->timestamps();
    });
});

it('assesses Fortify-compatible legacy identities without changing either database', function () {
    DB::connection('legacy')->table('users')->insert([
        legacyUserImportRow(41, [
            'family_name' => 'Verified',
            'email' => 'verified@example.test',
            'remember_token' => 'verified-remember-token',
        ]),
        legacyUserImportRow(42, [
            'family_name' => 'Unverified',
            'email' => 'unverified@example.test',
            'email_verified' => false,
            'status' => 'setting-up',
            'active' => false,
            'created_at' => '2020-02-01 00:00:00',
            'updated_at' => '2020-02-01 00:00:00',
        ]),
    ]);

    $canonicalUser = User::factory()->create();
    $canonicalUserCount = User::count();
    $legacyUsersBefore = DB::connection('legacy')->table('users')->orderBy('id')->get();

    $this->artisan('legacy:import-users', ['--dry-run' => true])
        ->expectsOutputToContain('Legacy user import dry run')
        ->expectsOutputToContain('Total legacy users: 2')
        ->expectsOutputToContain('Ready for identity import: 2')
        ->expectsOutputToContain('Compatible password hashes: 2')
        ->expectsOutputToContain('Verified email state: 1')
        ->expectsOutputToContain('Unverified email state: 1')
        ->expectsOutputToContain('Invalid legacy IDs: 0')
        ->expectsOutputToContain('Existing canonical IDs: 0')
        ->expectsOutputToContain('Invalid email verification states: 0')
        ->expectsOutputToContain('Identity anomalies: 0')
        ->doesntExpectOutputToContain('verified@example.test')
        ->doesntExpectOutputToContain('legacy-secret')
        ->doesntExpectOutputToContain('verified-remember-token')
        ->assertSuccessful();

    expect(User::count())->toBe($canonicalUserCount)
        ->and($canonicalUser->fresh())->not->toBeNull()
        ->and(DB::connection('legacy')->table('users')->orderBy('id')->get())->toEqual($legacyUsersBefore);
});

it('reports identity anomalies as aggregate counts without exposing affected members', function () {
    $compatibleHash = password_hash('legacy-secret', PASSWORD_BCRYPT, ['cost' => 10]);

    DB::connection('legacy')->table('users')->insert([
        legacyUserImportRow(1, [
            'given_name' => 'Ready',
            'email' => 'ready@example.test',
            'password' => $compatibleHash,
        ]),
        legacyUserImportRow(-2, [
            'given_name' => '',
            'family_name' => '',
            'email' => 'missing-name@example.test',
            'email_verified' => false,
            'password' => $compatibleHash,
            'status' => 'setting-up',
            'active' => false,
        ]),
        legacyUserImportRow(3, [
            'given_name' => 'Invalid',
            'family_name' => 'Email',
            'email' => 'private-invalid-email-value',
            'email_verified' => false,
            'password' => $compatibleHash,
        ]),
        legacyUserImportRow(4, [
            'given_name' => 'Missing',
            'family_name' => 'Password',
            'email' => 'missing-password@example.test',
            'email_verified' => false,
            'password' => null,
        ]),
        legacyUserImportRow(5, [
            'given_name' => 'Unsupported',
            'family_name' => 'Password',
            'email' => 'unsupported-password@example.test',
            'email_verified' => false,
            'password' => 'private-unsupported-password-value',
        ]),
        legacyUserImportRow(6, [
            'given_name' => 'Unknown',
            'family_name' => 'Status',
            'email' => 'unknown-status@example.test',
            'email_verified' => 2,
            'password' => $compatibleHash,
            'status' => 'private-unknown-status-value',
            'active' => false,
        ]),
    ]);

    $this->artisan('legacy:import-users', ['--dry-run' => true])
        ->expectsOutputToContain('Total legacy users: 6')
        ->expectsOutputToContain('Ready for identity import: 1')
        ->expectsOutputToContain('Compatible password hashes: 4')
        ->expectsOutputToContain('Verified email state: 1')
        ->expectsOutputToContain('Unverified email state: 4')
        ->expectsOutputToContain('Missing names: 1')
        ->expectsOutputToContain('Invalid emails: 1')
        ->expectsOutputToContain('Missing password hashes: 1')
        ->expectsOutputToContain('Incompatible password hashes: 1')
        ->expectsOutputToContain('Unknown lifecycle statuses: 1')
        ->expectsOutputToContain('Invalid legacy IDs: 1')
        ->expectsOutputToContain('Invalid email verification states: 1')
        ->expectsOutputToContain('Identity anomalies: 5')
        ->doesntExpectOutputToContain('private-invalid-email-value')
        ->doesntExpectOutputToContain('private-unsupported-password-value')
        ->doesntExpectOutputToContain('private-unknown-status-value')
        ->assertFailed();
});

it('reports normalized legacy duplicates and canonical email conflicts', function () {
    DB::connection('legacy')->table('users')->insert([
        legacyUserImportRow(1, ['email' => ' duplicate@example.test ']),
        legacyUserImportRow(2, ['email' => 'DUPLICATE@example.test']),
        legacyUserImportRow(3, ['email' => 'existing@example.test']),
    ]);
    User::factory()->create([
        'id' => 1,
        'email' => 'EXISTING@example.test',
    ]);

    $this->artisan('legacy:import-users', ['--dry-run' => true])
        ->expectsOutputToContain('Total legacy users: 3')
        ->expectsOutputToContain('Ready for identity import: 0')
        ->expectsOutputToContain('Duplicate legacy emails: 2')
        ->expectsOutputToContain('Existing canonical emails: 1')
        ->expectsOutputToContain('Existing canonical IDs: 1')
        ->expectsOutputToContain('Identity anomalies: 3')
        ->doesntExpectOutputToContain('duplicate@example.test')
        ->doesntExpectOutputToContain('existing@example.test')
        ->assertFailed();
});

it('imports compatible legacy identities using the explicit verification cutover timestamp', function () {
    $verifiedHash = password_hash('verified-secret', PASSWORD_BCRYPT, ['cost' => 10]);
    $unverifiedHash = password_hash('unverified-secret', PASSWORD_BCRYPT, ['cost' => 10]);
    User::factory()->create([
        'email' => 'existing-canonical@example.test',
    ]);

    DB::connection('legacy')->table('users')->insert([
        legacyUserImportRow(41, [
            'given_name' => '  Synthetic ',
            'family_name' => ' Verified  ',
            'email' => ' VERIFIED@example.test ',
            'email_verified' => '1',
            'password' => $verifiedHash,
            'remember_token' => 'legacy-remember-token',
            'updated_at' => '2021-03-04 05:06:07',
        ]),
        legacyUserImportRow(42, [
            'given_name' => 'Synthetic',
            'family_name' => 'Unverified',
            'email' => 'unverified@example.test',
            'email_verified' => '0',
            'password' => $unverifiedHash,
            'remember_token' => 'another-legacy-token',
        ]),
    ]);
    $legacyUsersBefore = DB::connection('legacy')->table('users')->orderBy('id')->get();

    $this->artisan('legacy:import-users', [
        '--commit' => true,
        '--verified-at' => '2026-08-27T18:00:00+01:00',
    ])
        ->expectsOutputToContain('Canonical users before import: 1')
        ->expectsOutputToContain('Imported legacy users: 2')
        ->expectsOutputToContain('Canonical users after import: 3')
        ->expectsOutputToContain('Canonical user count reconciled: 3')
        ->doesntExpectOutputToContain('verified@example.test')
        ->doesntExpectOutputToContain($verifiedHash)
        ->doesntExpectOutputToContain('legacy-remember-token')
        ->assertSuccessful();

    $verifiedUser = User::query()->findOrFail(41);
    $unverifiedUser = User::query()->findOrFail(42);

    expect($verifiedUser->name)->toBe('Synthetic Verified')
        ->and($verifiedUser->email)->toBe('verified@example.test')
        ->and($verifiedUser->email_verified_at?->toISOString())->toBe('2026-08-27T17:00:00.000000Z')
        ->and($verifiedUser->password)->toBe($verifiedHash)
        ->and(Hash::check('verified-secret', $verifiedUser->password))->toBeTrue()
        ->and($verifiedUser->remember_token)->toBeNull()
        ->and($verifiedUser->created_at?->toDateTimeString())->toBe('2020-01-01 00:00:00')
        ->and($verifiedUser->updated_at?->toDateTimeString())->toBe('2021-03-04 05:06:07')
        ->and($unverifiedUser->email_verified_at)->toBeNull()
        ->and($unverifiedUser->password)->toBe($unverifiedHash)
        ->and(Hash::check('unverified-secret', $unverifiedUser->password))->toBeTrue()
        ->and(DB::connection('legacy')->table('users')->orderBy('id')->get())->toEqual($legacyUsersBefore);

    $canonicalUsersAfterImport = DB::table('users')->orderBy('id')->get();

    $this->artisan('legacy:import-users', [
        '--commit' => true,
        '--verified-at' => '2026-08-27T18:00:00+01:00',
    ])
        ->expectsOutputToContain('Existing canonical emails: 2')
        ->expectsOutputToContain('Existing canonical IDs: 2')
        ->expectsOutputToContain('Import blocked: resolve 2 identity anomalies before committing. No data was changed.')
        ->assertFailed();

    expect(DB::table('users')->orderBy('id')->get())->toEqual($canonicalUsersAfterImport);
});

it('authenticates an imported legacy password through Fortify and upgrades its hash', function () {
    $legacyHash = password_hash('legacy-login-secret', PASSWORD_BCRYPT, ['cost' => 8]);

    DB::connection('legacy')->table('users')->insert(legacyUserImportRow(41, [
        'email' => ' LEGACY-LOGIN@example.test ',
        'password' => $legacyHash,
    ]));

    $this->artisan('legacy:import-users', [
        '--commit' => true,
        '--verified-at' => '2026-08-27T18:00:00+01:00',
    ])->assertSuccessful();

    $importedUser = User::query()->findOrFail(41);

    expect($importedUser->password)->toBe($legacyHash)
        ->and(Hash::needsRehash($importedUser->password))->toBeTrue();

    $this->withSession(['legacy-login-test' => true]);
    $sessionIdBeforeLogin = session()->getId();

    $response = $this->post(route('login.store'), [
        'email' => 'legacy-login@example.test',
        'password' => 'legacy-login-secret',
    ]);

    $response->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticatedAs($importedUser);

    $upgradedHash = $importedUser->fresh()->password;

    expect(session()->getId())->not->toBe($sessionIdBeforeLogin)
        ->and($upgradedHash)->not->toBe($legacyHash)
        ->and(Hash::check('legacy-login-secret', $upgradedHash))->toBeTrue()
        ->and(Hash::needsRehash($upgradedHash))->toBeFalse();
});

it('requires an explicit verification cutover timestamp before importing', function () {
    DB::connection('legacy')->table('users')->insert(legacyUserImportRow(41));

    $this->artisan('legacy:import-users', ['--commit' => true])
        ->expectsOutputToContain('A valid --verified-at ISO 8601 cutover timestamp is required. No data was changed.')
        ->assertFailed();

    expect(User::count())->toBe(0);
});

it('rejects a verification cutover timestamp that is not ISO 8601', function () {
    DB::connection('legacy')->table('users')->insert(legacyUserImportRow(41));

    $this->artisan('legacy:import-users', [
        '--commit' => true,
        '--verified-at' => 'August 27 2026 at 6pm',
    ])
        ->expectsOutputToContain('A valid --verified-at ISO 8601 cutover timestamp is required. No data was changed.')
        ->assertFailed();

    expect(User::count())->toBe(0);
});

it('blocks a commit when the assessment finds an identity anomaly', function () {
    DB::connection('legacy')->table('users')->insert(legacyUserImportRow(41, [
        'email' => 'private-invalid-email-value',
    ]));
    $legacyUserBefore = DB::connection('legacy')->table('users')->find(41);

    $this->artisan('legacy:import-users', [
        '--commit' => true,
        '--verified-at' => '2026-08-27T18:00:00+01:00',
    ])
        ->expectsOutputToContain('Identity anomalies: 1')
        ->expectsOutputToContain('Import blocked: resolve 1 identity anomaly before committing. No data was changed.')
        ->doesntExpectOutputToContain('private-invalid-email-value')
        ->assertFailed();

    expect(User::count())->toBe(0)
        ->and(DB::connection('legacy')->table('users')->find(41))->toEqual($legacyUserBefore);
});

it('blocks a commit when legacy account timestamps cannot be preserved', function () {
    DB::connection('legacy')->table('users')->insert(legacyUserImportRow(41, [
        'created_at' => '0000-00-00 00:00:00',
    ]));

    $this->artisan('legacy:import-users', [
        '--commit' => true,
        '--verified-at' => '2026-08-27T18:00:00+01:00',
    ])
        ->expectsOutputToContain('Invalid legacy timestamps: 1')
        ->expectsOutputToContain('Import blocked: resolve 1 identity anomaly before committing. No data was changed.')
        ->assertFailed();

    expect(User::count())->toBe(0);
});

it('rolls back every canonical user when a committed import fails', function () {
    DB::connection('legacy')->table('users')->insert([
        legacyUserImportRow(41),
        legacyUserImportRow(42),
    ]);
    $legacyUsersBefore = DB::connection('legacy')->table('users')->orderBy('id')->get();
    DB::listen(function (QueryExecuted $query): void {
        if ($query->connectionName === config('database.default')
            && preg_match('/insert into [`"]?users[`"]?/i', $query->sql) === 1) {
            throw new RuntimeException('private canonical database failure');
        }
    });

    $this->artisan('legacy:import-users', [
        '--commit' => true,
        '--verified-at' => '2026-08-27T18:00:00+01:00',
    ])
        ->expectsOutputToContain('Unable to import users into the canonical database. No data was changed.')
        ->doesntExpectOutputToContain('private canonical database failure')
        ->assertFailed();

    expect(User::count())->toBe(0)
        ->and(DB::connection('legacy')->table('users')->orderBy('id')->get())->toEqual($legacyUsersBefore);
});

it('rolls back every canonical user when imported row counts do not reconcile', function () {
    DB::connection('legacy')->table('users')->insert([
        legacyUserImportRow(41),
        legacyUserImportRow(42),
    ]);
    $legacyUsersBefore = DB::connection('legacy')->table('users')->orderBy('id')->get();
    DB::listen(function (QueryExecuted $query): void {
        if ($query->connectionName === config('database.default')
            && preg_match('/insert into [`"]?users[`"]?/i', $query->sql) === 1) {
            DB::table('users')->where('id', 42)->delete();
        }
    });

    $this->artisan('legacy:import-users', [
        '--commit' => true,
        '--verified-at' => '2026-08-27T18:00:00+01:00',
    ])
        ->expectsOutputToContain('Unable to import users into the canonical database. No data was changed.')
        ->doesntExpectOutputToContain('did not reconcile')
        ->assertFailed();

    expect(User::count())->toBe(0)
        ->and(DB::connection('legacy')->table('users')->orderBy('id')->get())->toEqual($legacyUsersBefore);
});

it('requires exactly one execution mode', function () {
    $this->artisan('legacy:import-users')
        ->expectsOutputToContain('Choose exactly one of --dry-run or --commit. No data was changed.')
        ->assertFailed();

    $this->artisan('legacy:import-users', [
        '--dry-run' => true,
        '--commit' => true,
        '--verified-at' => '2026-08-27T18:00:00+01:00',
    ])
        ->expectsOutputToContain('Choose exactly one of --dry-run or --commit. No data was changed.')
        ->assertFailed();

    expect(User::count())->toBe(0)
        ->and(DB::connection('legacy')->table('users')->count())->toBe(0);
});

it('fails without leaking connection details when legacy users cannot be read', function () {
    DB::shouldReceive('connection')
        ->with('legacy')
        ->andThrow(new RuntimeException('Rejected legacy_user:legacy_password at legacy.internal'));

    $this->artisan('legacy:import-users', ['--dry-run' => true])
        ->expectsOutputToContain('Unable to assess legacy user import compatibility.')
        ->doesntExpectOutputToContain('legacy_user')
        ->doesntExpectOutputToContain('legacy_password')
        ->doesntExpectOutputToContain('legacy.internal')
        ->assertFailed();
});
