<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Notification extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'notification_body'
    ];

    public $incrementing = false;

    protected $dates = ['deleted_at'];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            $user->id = strtoupper(Str::random(6));
        });
    }

    public function admin()
    {
        return $this->belongsTo(User::class);
    }

    public function enterprise()
    {
        return $this->belongsTo(User::class);
    }

    public function household()
    {
        return $this->belongsTo(User::class);
    }

    public function collector()
    {
        return $this->belongsTo(User::class);
    }

    public function host()
    {
        return $this->belongsTo(User::class);
    }
}
