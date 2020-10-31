<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    protected $fillable = [
        'name',
        'price',
        'image'
    ];

    public $timestamps = true;

    protected $attributes = [

    ];

}
