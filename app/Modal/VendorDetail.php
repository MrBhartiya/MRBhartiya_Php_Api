<?php

namespace App\Modal;

use Illuminate\Database\Eloquent\Model;

class VendorDetail extends Model
{
    //
    protected $guarded=['id'];
    public function referralTran(){
       return $this->hasMany( ReferralTran::class, "referred_user_id", "user_id");
    }
}
