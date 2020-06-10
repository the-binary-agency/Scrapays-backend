<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class materialPrices extends Model
{
    protected $fillable = ['material', 'price'];

    public $timestamps = true;

    public $incrementing = false;

    protected $attributes = [
      
    ];

}
