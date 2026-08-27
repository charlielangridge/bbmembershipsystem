<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

#[Signature('legacy:preflight')]
#[Description('Validate the legacy identity schema without reading member records')]
class LegacyDatabasePreflightCommand extends Command
{
    public function handle(): int
    {
        try {
            foreach ($this->schemaContract() as $table => $columns) {
                if (! Schema::connection('legacy')->hasTable($table)) {
                    $this->components->error("Missing legacy table: {$table}");

                    return self::FAILURE;
                }

                $actualColumns = Schema::connection('legacy')->getColumnListing($table);
                $missingColumns = array_diff(
                    $columns['required'],
                    $actualColumns,
                );

                if ($missingColumns !== []) {
                    $this->components->error("Legacy table {$table} is missing columns: ".Arr::join($missingColumns, ', '));

                    return self::FAILURE;
                }

                $additionalColumns = array_diff(
                    $actualColumns,
                    $columns['required'],
                    $columns['optional'],
                );

                if ($additionalColumns !== []) {
                    $this->components->warn("Additional legacy columns detected in {$table}: ".Arr::join($additionalColumns, ', '));
                }
            }

            $this->components->info('Legacy identity schema is compatible.');

            foreach (array_keys($this->schemaContract()) as $table) {
                $rowCount = DB::connection('legacy')->table($table)->count();
                $label = $rowCount === 1 ? 'row' : 'rows';

                $this->line("{$table}: {$rowCount} {$label}");
            }

            return self::SUCCESS;
        } catch (Throwable) {
            $this->components->error('Unable to inspect the configured legacy database connection.');

            return self::FAILURE;
        }
    }

    /**
     * @return array<string, array{required: list<string>, optional: list<string>}>
     */
    private function schemaContract(): array
    {
        return [
            'users' => [
                'required' => [
                    'id',
                    'given_name',
                    'family_name',
                    'email',
                    'email_verified',
                    'password',
                    'remember_token',
                    'status',
                    'active',
                    'created_at',
                    'updated_at',
                ],
                'optional' => [
                    'hash',
                    'import_match_string',
                    'secondary_email',
                    'phone',
                    'emergency_contact',
                    'notes',
                    'founder',
                    'director',
                    'trusted',
                    'key_holder',
                    'key_deposit_payment_id',
                    'storage_box_payment_id',
                    'induction_completed',
                    'payment_method',
                    'payment_day',
                    'monthly_subscription',
                    'subscription_id',
                    'last_subscription_payment',
                    'subscription_expires',
                    'cash_balance',
                    'profile_private',
                    'banned_date',
                    'banned_reason',
                    'rules_agreed',
                ],
            ],
            'profile_data' => [
                'required' => ['id', 'user_id', 'description', 'skills_array', 'profile_photo', 'created_at', 'updated_at'],
                'optional' => [
                    'twitter',
                    'facebook',
                    'google_plus',
                    'github',
                    'irc',
                    'website',
                    'tagline',
                    'profile_photo_on_wall',
                    'profile_photo_private',
                    'new_profile_photo',
                ],
            ],
            'user_address' => [
                'required' => ['id', 'user_id', 'postcode', 'approved', 'created_at', 'updated_at'],
                'optional' => ['line_1', 'line_2', 'line_3', 'line_4', 'hash'],
            ],
            'roles' => [
                'required' => ['id', 'name', 'title', 'created_at', 'updated_at'],
                'optional' => [],
            ],
            'role_user' => [
                'required' => ['id', 'role_id', 'user_id', 'created_at', 'updated_at'],
                'optional' => [],
            ],
        ];
    }
}
