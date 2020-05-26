<?php

namespace App\Modal;

use Illuminate\Database\Eloquent\Model;

class Classe extends Model
{
    //
    protected $guarded=['id'];

    public function subject(){
        return $this->hasMany(Subject::class, "class_id", "id");
    }

}
