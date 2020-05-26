<?php

namespace App\Modal;

use Illuminate\Database\Eloquent\Model;

class ReferralTran extends Model
{
    //
    protected $guarded=['id'];
    public function order(){
        return $this->hasOne(Order::class, "id", "order_id");
    }
}
