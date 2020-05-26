<?php

namespace App\Modal;

use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    //
    protected $guarded=['id'];
    protected $table='quiz';

    public function quiz_question(){
        return $this->hasMany(QuizQuestion::class, "quiz_id", "id");
    }
    public function TopicVideo(){
        return $this->hasOne( TopicVideo::class, "id", "video_id");
    }

}
