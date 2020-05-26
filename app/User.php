<?php

namespace App;

use App\Modal\Order;
use App\Modal\RedeemBalance;
use App\Modal\ReferralTran;
use App\Modal\StudentDetail;
use App\Modal\UserSubscription;
use App\Modal\VendorDetail;
use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Zizaco\Entrust\Traits\EntrustUserTrait;
use Illuminate\Foundation\Auth\User as Authenticatable;



class User extends Authenticatable
{
    use EntrustUserTrait;
    use HasApiTokens, Notifiable;
    use Notifiable;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded=['id'];
    /*protected $fillable = [
        'name', 'email', 'password',
    ];*/

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token','email_verified_at'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function studentDetails(){
        return $this->hasOne(StudentDetail::class, "user_id", "id");
    }
    public function vendorDetails(){
        return $this->hasOne(VendorDetail::class, "user_id", "id");
    }
    public function routeNotificationForFcm()
    {
        return $this->fcm_token;
    }
    public function order(){
        return $this->hasOne(Order::class, "referral_user_id", "id");
    }
    public function order_hist(){
        return $this->hasMany(Order::class, "user_id", "id");
    }
    public function referral(){
        return $this->hasOne(ReferralTran::class, "referral_user_id", "id");
    }
    public function userSubscription(){
        return $this->hasOne(UserSubscription::class, "user_id", "id")->latest();
    }
    public function redeemHistory(){
        return $this->hasMany(RedeemBalance::class, "user_id", "id");
    }
}
