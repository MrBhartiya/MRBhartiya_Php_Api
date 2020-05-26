<?php

namespace App\Modal;

use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    //
    protected $guarded=['id'];
    public function video(){
        return $this->hasMany( TopicVideo::class, "teacher_id", "id");
    }
}
