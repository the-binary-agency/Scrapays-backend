<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class collectedScrap extends Model
{
    protected $fillable = ['producerID', 'collectorID', 'vendorID', 'metal', 'aluminium', 'paper', 'plastic', 'others', 'vendorApproved'];
    protected $primaryKey = "id";

    public $timestamps = true;

    public $incrementing = false;

    protected $attributes = [
       'vendorApproved' => ''
    ];

    public static function boot()
{
    parent::boot();

    static::creating(function ($listedscrap) {
        $listedscrap->id = strtoupper(Str::random(6));
    });
}
}
