<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Agent extends Model
{
    use SoftDeletes;
/**
 * The attributes that should be hidden for arrays.
 *
 * @var array
 */
    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    protected $dates = ['deleted_at'];

    public function user()
    {
        return $this->morphOne('App\User', 'userable');
    }

}
