<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Host extends Model
{
    public function user() 
  { 
    return $this->morphOne('App\User', 'userable');
  }
  
}
