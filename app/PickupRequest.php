<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PickupRequest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'enterprise_id',
        'enterprise_phone',
        'materials',
        'address',
        'schedule',
        'comment',
        'description',
        'producer_name'
    ];

    public $timestamps = true;

    public $incrementing = false;

    protected $dates = ['deleted_at'];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            $user->id = strtoupper(Str::random(6));
        });
    }

    public function producer()
    {
        return $this->belongsTo(User::class);
    }

    public function collectedScrap()
    {
        return $this->hasOne(CollectedScrap::class, 'pickup_id');
    }

    public function collector()
    {
        return $this->belongsTo(User::class);
    }
}
