<?php

namespace App\Modal;

use Illuminate\Database\Eloquent\Model;

class FavouriteVideo extends Model
{
    //
    protected $guarded=['id'];
    public function video(){
        return $this->hasOne( TopicVideo::class, "id", "video_id");
    }
}
