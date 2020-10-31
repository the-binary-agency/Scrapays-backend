<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Enterprise extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_name',
        'company_size',
        'address',
        'industry',
        'gender'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'admin_automated'
    ];

    protected $dates = ['deleted_at'];

    protected $table = "enterprises";

    protected $primaryKey = "id";

    public $timestamps = true;

    public function user()
    {
        return $this->morphOne('App\User', 'userable');
    }

}
