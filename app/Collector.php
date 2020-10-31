<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Collector extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'collection_coverage_zone',
        'approved_as_collector'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'created_at',
        'updated_at',
        'current_location'
    ];

    protected $dates = ['deleted_at'];

    public function user()
    {
        return $this->morphOne('App\User', 'userable');
    }
}
