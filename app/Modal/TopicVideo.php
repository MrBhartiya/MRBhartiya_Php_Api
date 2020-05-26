<?php

namespace App\Modal;

use Illuminate\Database\Eloquent\Model;

class TopicVideo extends Model
{
    //
    protected $guarded=['id'];
    public function topic(){
        return $this->hasOne(Topic::class, "id", "topic_id");
    }
    public function teacherVideo(){
        return $this->hasOne(Teacher::class, "id", "teacher_id");
    }
    public function quiz(){
        return $this->hasOne(Quiz::class, "video_id", "id");
    }
}
