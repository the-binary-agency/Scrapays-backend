<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Household extends Model
{
    protected $fillable = [ 'requestAddress' ];

    protected $table = "households";

    protected $primaryKey = "id";

    public $timestamps = true;
    
    public function user() 
  { 
    return $this->morphOne('App\User', 'userable');
  }
}
