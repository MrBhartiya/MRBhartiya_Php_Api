<?php

namespace App\Modal;

use Illuminate\Database\Eloquent\Model;

class Topic extends Model
{
    //
    protected $guarded=['id'];
    public function chapter(){
        return $this->hasOne(Chapter::class, "id", "chapter_id");
    }
    public function topicVideo(){
        return $this->hasMany(TopicVideo::class, "topic_id", "id");
    }
}
