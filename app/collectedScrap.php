<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class collectedScrap extends Model
{

 /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [ 'collectorID', 'producerPhone', 'cost', 'materials', 'paymentMethod', 'totalTonnage', 'address', 'pickupID'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
     'id', 'updated_at'
    ];

    protected $primaryKey = "id";

    public $timestamps = true;

    public $incrementing = false;

    public static function boot()
{
    parent::boot();

    static::creating(function ($listedscrap) {
        $listedscrap->id = strtoupper(Str::random(6));
    });
}
}
