<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Collector extends Model
{
  protected $fillable = [ 'collectionCoverageZone', 'approvedAsCollector' ];

    public function user() 
  { 
    return $this->morphOne('App\User', 'userable');
  }
}
