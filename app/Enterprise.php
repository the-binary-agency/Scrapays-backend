<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Enterprise extends Model
{
    protected $fillable = [ 'companyName', 'companySize', 'address', 'industry', 'sex' ];

    protected $table = "enterprises";

    protected $primaryKey = "id";

    public $timestamps = true;

    public function user() 
  { 
    return $this->morphOne('App\User', 'userable');
  }
}
