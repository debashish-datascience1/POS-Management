<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductionStock extends Model
{
    protected $table = 'production_stock';

    protected $fillable = [
        'product_id',
        'location_id',
        'total_raw_material',
    ];

    /**
     * Get the product that owns the production stock.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the location that owns the production stock.
     */
    public function location()
    {
        return $this->belongsTo(BusinessLocation::class, 'location_id');
    }
}