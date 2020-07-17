<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class materialPrices extends Model
{
    protected $fillable = ['name', 'price', 'image'];

    public $timestamps = true;

    protected $attributes = [
      
    ];

}
