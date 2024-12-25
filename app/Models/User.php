<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Lanos\CashierConnect\Contracts\StripeAccount;
use App\Permissions\HasPermissionsTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Notifications\ResetPasswordNotification;
use App\Traits\ReservedKeywordsTrait;
use Illuminate\Database\Eloquent\Model;
use Laravel\Cashier\Billable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Lanos\CashierConnect\Billable as ConnectBillable;

class User extends Authenticatable implements HasMedia, StripeAccount
{
    use HasFactory, Notifiable, HasPermissionsTrait, HasApiTokens, Billable, ReservedKeywordsTrait, InteractsWithMedia, ConnectBillable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'store_name',
        'username',
        'image',
        'reg_source',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
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
            'branding' => 'boolean',
        ];
    }

    public function createAuthToken()
    {
        return $this->createToken('auth_token', $this->getPermissionsThroughRole()->toArray())->plainTextToken;
    }

    /**
     * Send a password reset notification to the user.
     *
     * @param  string  $token
     */
    public function sendPasswordResetNotification($token) {
        $url = url("/reset-password?token={$token}&email={$this->email}");
        $frontent_app_url = config('app.frontend_url', $url );

        $url = $frontent_app_url . "?token={$token}&email={$this->email}";

        $this->notify(new ResetPasswordNotification($url));
    }

    public function banks()
    {
        return $this->hasMany(BankAccount::class);
    }

    public function onboard()
    {
        return $this->hasOne(Onboard::class);
    }
    function userDetails(){
        return $this->hasOne(UserDetails::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'user_id');
    }

    function wise(){
        return $this->hasOne(Wise::class);
    }
    function stripeConnect(){
        return $this->hasOne(ConnectStripe::class);
    }

    public function defaultWithdrawMethod(){
        return $this->hasOne(DefaultWithdrawMethod::class);
    }
    public function googleCalendarToken()
    {
        return $this->hasOne(GoogleCalendarToken::class);
    }

    public function zoomConnect()
    {
        return $this->hasOne(Zoom::class, 'user_id');
    }

}
