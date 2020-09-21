<?php

namespace App;

use App\Http\Controllers\AuthController;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Bavix\Wallet\Traits\HasWallet;
use Bavix\Wallet\Interfaces\Wallet;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;
    // use HasWallet;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [ 'firstName', 'lastName', 'phone', 'email' ];

    protected $primaryKey = "phone";

    public $timestamps = true;

    public $incrementing = false;

    protected $with = ['userable'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'userable_id', 'pin', 'updated_at', 'inviteCode', 'email_verified_at', 'api_token'
    ];

    protected $attributes = [
        'avatarImage' => '',   
        'inviteCode' => ''
    ];


    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function userable()
    {
        return $this->morphTo();
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return ['userable_type' => $this->userable_type, 'phone' => $this->phone];
    }

    public function setPasswordAttribute($value){
        $this->attributes['password']  = bcrypt($value);
    }

    public static function boot()
{
    parent::boot();

    static::creating(function ($user) {
        $user->id = strtoupper(Str::random(9));
    });
}
}
