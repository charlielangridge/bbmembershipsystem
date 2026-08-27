<?php

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
    User::factory()->create(['email' => 'EXISTING@example.test']);

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

it('refuses to write until an explicit import mode is implemented', function () {
    $this->artisan('legacy:import-users')
        ->expectsOutputToContain('Only --dry-run is currently available. No data was changed.')
        ->assertFailed();

    expect(User::count())->toBe(0)
        ->and(DB::connection('legacy')->table('users')->count())->toBe(0);
});

it('fails without leaking connection details when legacy users cannot be read', function () {
    DB::shouldReceive('connection')
        ->with('legacy')
        ->andThrow(new RuntimeException('Rejected legacy_user:legacy_password at legacy.internal'));

    $this->artisan('legacy:import-users', ['--dry-run' => true])
        ->expectsOutputToContain('Unable to assess users from the configured legacy database connection.')
        ->doesntExpectOutputToContain('legacy_user')
        ->doesntExpectOutputToContain('legacy_password')
        ->doesntExpectOutputToContain('legacy.internal')
        ->assertFailed();
});
