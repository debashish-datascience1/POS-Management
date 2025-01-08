<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\BusinessLocation;

class FinalProduct extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'final_product';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'date',
        'business_id',
        'temperature',
        'product_temperature',
        'quantity',
        'mix',
        'total',
        'grand_total',
        'location_id'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'temperature' => 'array',
        'product_temperature' => 'array',
        'quantity' => 'array',
        'mix' => 'array',
        'total' => 'array',
        'date' => 'date'
    ];

    /**
     * Relationship with Business Location
     */
    public function location()
    {
        return $this->belongsTo(BusinessLocation::class, 'location_id');
    }
}