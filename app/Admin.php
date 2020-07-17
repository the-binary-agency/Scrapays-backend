<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Admin extends Model
{
    public function user() 
  { 
    return $this->morphOne('App\User', 'userable');
  }

}
