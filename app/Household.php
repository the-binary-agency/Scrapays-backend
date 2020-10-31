<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Household extends Model
{
    use SoftDeletes;

    protected $fillable = ['request_address'];

    protected $table = "households";

    protected $primaryKey = "id";

    public $timestamps = true;

    protected $dates = ['deleted_at'];

    public function user()
    {
        return $this->morphOne('App\User', 'userable');
    }

}
