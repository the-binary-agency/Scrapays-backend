<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;


class Notification extends Model
{
    protected $fillable = [ 'user_id', 'notification_body' ];

    public $incrementing = false;
    
    public static function boot()
{
    parent::boot();

    static::creating(function ($user) {
        $user->id = strtoupper(Str::random(6));
    });
}
}
