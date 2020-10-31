<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'phone',
        'email'
    ];

    protected $primaryKey = "id";

    public $timestamps = true;

    public $incrementing = false;

    protected $with = ['userable'];

    protected $dates = ['deleted_at'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'userable_id',
        'pin',
        'updated_at',
        'invite_code',
        'email_verified_at',
        'api_token'
    ];

    protected $attributes = [
        'avatar_image' => '',
        'invite_code'  => ''
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime'
    ];

    public function userable()
    {
        return $this->morphTo();
    }

    public function setPhoneAttribute($phone)
    {
        $this->attributes['phone'] = '+234' . substr($phone, 1);
    }

    public function setPinAttribute($pin)
    {
        $this->attributes['pin'] = Crypt::encryptString($pin);
    }

    public function getfirstNameAttribute($first_name)
    {
        return ucwords($first_name);
    }

    public function getlastNameAttribute($last_name)
    {
        return ucwords($last_name);
    }

    public function getPhoneAttribute($phone)
    {
        return '0' . explode('+234', $phone)[1];
    }

    public function collectedScrap()
    {
        return $this->hasMany(CollectedScrap::class, 'collector_id');
    }

    public function producedScrap()
    {
        return $this->hasMany(CollectedScrap::class, 'producer_id');
    }

    public function requestedPickup()
    {
        return $this->hasMany(PickupRequest::class, 'producer_id');
    }

    public function assignedPickup()
    {
        return $this->hasMany(PickupRequest::class, 'assigned_collector');
    }

    public function inventories()
    {
        return $this->hasMany(Inventory::class, 'enterprise_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
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
        if ($this->userable_type === 'Admin') {
            return ['userable_type' => $this->userable_type, 'id' => $this->id, 'permissions' => $this->userable->permisssions];
        } else {
            return ['userable_type' => $this->userable_type, 'id' => $this->id];

        }
    }

    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = bcrypt($value);
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            $user->id = strtoupper(Str::random(9));
        });
    }
}
