<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Map extends Model
{
    
    protected $fillable = [
        'file', 'levels', 'rows', 'columns',
    ];

    public function getThumbAttribute() {
        return 'maps/'.$this->id.'/thumb.jpg';
    }
    public function getActualAttribute() {
        return 'maps/'.$this->id.'/actual.jpg';
    }

}
