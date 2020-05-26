<?php

namespace App\Modal;

use App\User;
use Illuminate\Database\Eloquent\Model;

class RedeemBalance extends Model
{
    //
    protected $guarded=['id'];
    public function user(){
        return $this->hasOne(User::class, "id", "user_id");
    }
}
