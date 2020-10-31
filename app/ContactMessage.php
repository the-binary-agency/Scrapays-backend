<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ContactMessage extends Model
{
    protected $fillable = [
        'name',
        'email',
        'contact',
        'message'
    ];

    protected $primaryKey = "id";

    public $timestamps = true;

    public $incrementing = false;

    public static function boot()
    {
        parent::boot();

        static::creating(function ($contactmessage) {
            $contactmessage->id = strtoupper(Str::random(6));
        });
    }
}
