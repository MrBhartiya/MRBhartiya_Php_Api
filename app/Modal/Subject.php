<?php

namespace App\Modal;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    //
    protected $guarded=['id'];
    public function classes(){
        return $this->hasOne(Classe::class, "id", "class_id");
    }
    public function teacher(){
        return $this->hasOne(Teacher::class, "id", "teacher_id");
    }
    public function chapter(){
        return $this->hasMany(Chapter::class, "subject_id", "id");
    }
}
