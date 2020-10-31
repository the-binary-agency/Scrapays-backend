<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inventory extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];

    public function enterprise()
    {
        return $this->belongsTo(User::class);
    }
}
