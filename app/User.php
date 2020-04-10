<?php

namespace App;

use App\Http\Controllers\AuthController;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'firstName', 'lastName', 'phone', 'role', 'email', 'password', 'address', 'collectionCoverageZone', 'specificLocationAddress', 'RCNo', 'TIN', 'type', 'vendorApproved', 'vendorID'
    ];

    protected $primaryKey = "id";

    public $timestamps = true;

    public $incrementing = false;

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $attributes = [
       'address' => '',
       'collectionCoverageZone' => '',
       'specificLocationAddress' => '',
       'RCNo' => '',
       'TIN' => '',
       'type' => '',
       'vendorApproved' => '',
       'vendorID' => ''
    ];


    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // Rest omitted for brevity

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
        return ['role' => $this->role];
    }

    public function setPasswordAttribute($value){
        $this->attributes['password']  = bcrypt($value);
    }

    public static function boot()
{
    parent::boot();

    static::creating(function ($user) {
        $user->id = strtoupper(Str::random(6));
    });
}
}
