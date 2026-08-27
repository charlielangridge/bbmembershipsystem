<?php

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    config([
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

    Schema::connection('legacy')->create('profile_data', function (Blueprint $table): void {
        $table->unsignedInteger('id')->primary();
        $table->unsignedInteger('user_id');
        $table->text('description')->nullable();
        $table->string('skills_array')->nullable();
        $table->boolean('profile_photo')->default(false);
        $table->timestamps();
    });

    Schema::connection('legacy')->create('user_address', function (Blueprint $table): void {
        $table->unsignedInteger('id')->primary();
        $table->unsignedInteger('user_id');
        $table->string('postcode')->nullable();
        $table->boolean('approved');
        $table->timestamps();
    });

    Schema::connection('legacy')->create('roles', function (Blueprint $table): void {
        $table->unsignedInteger('id')->primary();
        $table->string('name');
        $table->string('title')->nullable();
        $table->timestamps();
    });

    Schema::connection('legacy')->create('role_user', function (Blueprint $table): void {
        $table->unsignedInteger('id')->primary();
        $table->unsignedInteger('role_id');
        $table->unsignedInteger('user_id');
        $table->timestamps();
    });
});

it('reports a compatible identity schema without exposing or changing member data', function () {
    DB::connection('legacy')->table('users')->insert([
        'id' => 42,
        'given_name' => 'Synthetic',
        'family_name' => 'Member',
        'email' => 'synthetic.member@example.test',
        'email_verified' => true,
        'password' => '$2y$10$synthetic.hash.value',
        'remember_token' => 'synthetic-remember-token',
        'status' => 'active',
        'active' => true,
        'created_at' => '2020-01-01 00:00:00',
        'updated_at' => '2020-01-01 00:00:00',
    ]);

    $canonicalUser = User::factory()->create();
    $legacyTables = ['users', 'profile_data', 'user_address', 'roles', 'role_user'];
    $canonicalTableCounts = fn (): array => collect(Schema::getTableListing())
        ->mapWithKeys(fn (string $table): array => [$table => DB::table($table)->count()])
        ->all();
    $legacyTableCounts = fn (): array => collect($legacyTables)
        ->mapWithKeys(fn (string $table): array => [$table => DB::connection('legacy')->table($table)->count()])
        ->all();
    $canonicalCountsBefore = $canonicalTableCounts();
    $legacyCountsBefore = $legacyTableCounts();
    $legacyUser = DB::connection('legacy')->table('users')->find(42);

    $this->artisan('legacy:preflight')
        ->expectsOutputToContain('Legacy identity schema is compatible.')
        ->expectsOutputToContain('users: 1 row')
        ->doesntExpectOutputToContain('Synthetic')
        ->doesntExpectOutputToContain('synthetic.member@example.test')
        ->doesntExpectOutputToContain('$2y$10$synthetic.hash.value')
        ->doesntExpectOutputToContain('synthetic-remember-token')
        ->assertSuccessful();

    expect($canonicalTableCounts())->toBe($canonicalCountsBefore)
        ->and(collect($canonicalUser->fresh()?->getAttributes())->sortKeys()->all())
        ->toBe(collect($canonicalUser->getAttributes())->sortKeys()->all())
        ->and($legacyTableCounts())->toBe($legacyCountsBefore)
        ->and(DB::connection('legacy')->table('users')->find(42))->toEqual($legacyUser);
});

it('reports an incompatible identity schema when a required column is missing', function () {
    Schema::connection('legacy')->table('users', function (Blueprint $table): void {
        $table->dropColumn('email_verified');
    });

    $this->artisan('legacy:preflight')
        ->expectsOutputToContain('Legacy table users is missing columns: email_verified')
        ->assertFailed();
});

it('reports an incompatible identity schema when a required table is missing', function () {
    Schema::connection('legacy')->drop('role_user');

    $this->artisan('legacy:preflight')
        ->expectsOutputToContain('Missing legacy table: role_user')
        ->assertFailed();
});

it('reports additional identity columns without rejecting the candidate schema', function () {
    Schema::connection('legacy')->table('users', function (Blueprint $table): void {
        $table->string('production_only_flag')->nullable();
    });

    $this->artisan('legacy:preflight')
        ->expectsOutputToContain('Additional legacy columns detected in users: production_only_flag')
        ->expectsOutputToContain('Legacy identity schema is compatible.')
        ->assertSuccessful();
});

it('fails safely when the legacy database cannot be inspected', function () {
    config([
        'database.connections.legacy' => [
            'driver' => 'mysql',
            'url' => 'mysql://legacy_user:legacy_password@legacy.internal/legacy',
            'username' => 'legacy_user',
            'password' => 'legacy_password',
        ],
    ]);

    DB::purge('legacy');
    Schema::shouldReceive('connection')
        ->with('legacy')
        ->andThrow(new RuntimeException('Connection rejected for legacy_user using legacy_password at legacy.internal'));

    $this->artisan('legacy:preflight')
        ->expectsOutputToContain('Unable to inspect the configured legacy database connection.')
        ->doesntExpectOutputToContain('legacy_user')
        ->doesntExpectOutputToContain('legacy_password')
        ->doesntExpectOutputToContain('legacy.internal')
        ->assertFailed();
});
