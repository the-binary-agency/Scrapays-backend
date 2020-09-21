<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PickupRequest extends Model
{
    protected $fillable = ['userID', 'materials', 'address', 'schedule', 'comment', 'description', 'producerName'];

    public $timestamps = true;

    public $incrementing = false;

    public static function boot()
{
    parent::boot();

    static::creating(function ($user) {
        $user->id = strtoupper(Str::random(6));
    });
}
}
