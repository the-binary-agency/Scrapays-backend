<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Collector extends Model
{
  protected $fillable = [ 'collectionCoverageZone', 'approvedAsCollector' ];

  /**
    * The attributes that should be hidden for arrays.
    *
    * @var array
    */
  protected $hidden = [
    'created_at', 'updated_at', 'current_loc'
  ];

    public function user() 
  { 
    return $this->morphOne('App\User', 'userable');
  }
}
