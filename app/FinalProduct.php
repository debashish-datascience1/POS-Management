<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FinalProduct extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'final_products';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'product_name', 
        'description', 
        'quantity', 
        'sum'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'quantity' => 'integer',
        'sum' => 'decimal:2'
    ];
}