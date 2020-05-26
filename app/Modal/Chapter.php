<?php

namespace App\Modal;

use Illuminate\Database\Eloquent\Model;

class Chapter extends Model
{
    //
    protected $guarded=['id'];
    public function subject(){
        return $this->hasOne(Subject::class, "id", "subject_id");
    }
    public function topic(){
        return $this->hasMany(Topic::class, "chapter_id", "id");
    }
}
