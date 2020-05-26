<?php

namespace App\Modal;

use App\User;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    //
    protected $guarded=['id'];
    public function user(){
        return $this->hasOne(User::class, "id", "user_id");
    }
    public function subscription(){
        return $this->hasOne(Subscription::class, "id", "subscription_id");
    }
}
