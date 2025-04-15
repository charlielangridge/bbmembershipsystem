<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Casts\MoneyCast;
use App\Enums\UserStatus;
use App\Observers\UserObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;


#[ObservedBy([UserObserver::class])]
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'given_name',
        'family_name',
        'import_match_string',
        'email',
        'slack_username',
        'email_verified_at',
        'secondary_email',
        'password',
        'phone',
        'emergency_contact',
        'notes',
        'active',
        'founder',
        'status',
        'trusted',
        'key_holder',
        'key_deposit_payment_id',
        'storage_box_payment_id',
        'induction_completed_at',
        'inducted_by',
        'payment_method',
        'secondary_payment_method',
        'payment_day',
        'monthly_subscription',
        'subscription_id',
        'gocardless_setup_id',
        'mandate_id',
        'last_subscription_payment',
        'subscription_expires',
        'cash_balance',
        'profile_private',
        'banned',
        'banned_reason',
        'banned_at',
        'rules_agreed_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean',
            'founder' => 'boolean',
            'status' => UserStatus::class,
            'trusted' => 'boolean',
            'key_holder' => 'boolean',
            'induction_completed_at' => 'datetime',
            'profile_private' => 'boolean',

            'payment_method',
            'secondary_payment_method',

            'monthly_subscription' => MoneyCast::class,

            'last_subscription_payment' => 'datetime',
            'subscription_expires' => 'datetime',
            'cash_balance' => MoneyCast::class,

            'banned_at' => 'datetime',
            'rules_agreed_at' => 'datetime',

        ];
    }

    protected function paymentDay(): Attribute
    {

        return Attribute::make(
        //Ensure the payment date will always exist on any month payment_date
            set: fn(int $value) => $value > 28 ? 1 : $value,
        );
    }


//    public function payments()
//    {
//        return $this->hasMany('\BB\Entities\Payment')->orderBy('created_at', 'desc');
//    }
//
//    public function inductions()
//    {
//        return $this->hasMany('\BB\Entities\Induction');
//    }
//
//    public function keyFob()
//    {
//        return $this->hasMany('\BB\Entities\KeyFob')->where('active', true)->first();
//    }
//
//    public function keyFobs()
//    {
//        return $this->hasMany('\BB\Entities\KeyFob')->where('active', true);
//    }
//
//    public function profile()
//    {
//        return $this->hasOne('\BB\Entities\ProfileData');
//    }
//
//    public function address()
//    {
//        return $this->hasOne('\BB\Entities\Address')->orderBy('approved', 'asc');
//    }
//
//    public function notifications()
//    {
//        return $this->hasMany(Notification::class)->orderBy('created_at', 'desc');
//    }

//'inducted_by',
//'subscription_id',
//'gocardless_setup_id',
//'mandate_id',

}
