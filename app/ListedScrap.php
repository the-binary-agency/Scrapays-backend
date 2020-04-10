<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ListedScrap extends Model
{
    protected $fillable = ['firstName', 'lastName', 'phone', 'email', 'materialImages', 'materialLocation','materialDescription'];
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
