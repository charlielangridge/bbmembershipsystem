<?php

use App\Enums\UserStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('given_name');
            $table->string('family_name');
//            $table->string('import_match_string', 50)->nullable();
            $table->string('email')->unique();
            $table->string('slack_username')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('secondary_email')->nullable();
            $table->string('password');
            $table->string('phone');
            $table->string('emergency_contact')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default(UserStatus::ACTIVE);

            $table->boolean('active')->default(false);
            $table->boolean('founder')->default(false);
            $table->boolean('trusted')->default(false);
            $table->boolean('key_holder')->default(false);
            $table->dateTime('induction_completed_at')->nullable();

            $table->unsignedBigInteger('key_deposit_payment_id')->nullable();
            $table->unsignedBigInteger('storage_box_payment_id')->nullable();
            $table->unsignedBigInteger('inducted_by')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('secondary_payment_method')->nullable();
            $table->integer('payment_day');
            $table->integer('monthly_subscription')->nullable();

            $table->string('subscription_id')->nullable();
            $table->string('gocardless_setup_id')->nullable();
            $table->string('mandate_id')->nullable();
            $table->dateTime('last_subscription_payment')->nullable();
            $table->dateTime('subscription_expires')->nullable();
            $table->integer('cash_balance')->default(0);
            $table->boolean('profile_private')->default(false);
//            $table->boolean('banned')->default(false);
            $table->string('banned_reason')->nullable();
            $table->dateTime('banned_at')->nullable();

            $table->dateTime('rules_agreed_at')->nullable();

            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
