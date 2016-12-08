<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tile extends Model
{
    protected $fillable = [
        'map_id', 'x', 'y', 'level', 'image_url', 'classification',
    ];

    public function map() {
        return $this->belongsTo('App\Map');
    }
}
