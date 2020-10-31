<?php

namespace App;

use App\Collector;
use App\Enterprise;
use App\Household;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CollectedScrap extends Model
{

    use SoftDeletes;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'cost',
        'materials',
        'payment_method',
        'total_tonnage',
        'address',
        'producer_id',
        'collector_id',
        'pickup_id'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'updated_at'
    ];

    protected $dates = ['deleted_at'];

    protected $primaryKey = "id";

    public $timestamps = true;

    public $incrementing = false;

    public static function boot()
    {
        parent::boot();

        static::creating(function ($listedscrap) {
            $listedscrap->id = strtoupper(Str::random(6));
        });
    }

    public function collector()
    {
        return $this->belongsTo(User::class);
    }

    public function household()
    {
        return $this->belongsTo(User::class);
    }

    public function enterprise()
    {
        return $this->belongsTo(User::class);
    }

    public function collectedScrap()
    {
        return $this->belongsTo(PickupRequest::class);
    }
}
