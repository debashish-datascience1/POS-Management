<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionUnit extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'business_id',
        'date',
        'raw_material',
        'products',
        'total_quantity',
        'location_id',

    ];

    protected $casts = [
        'date' => 'date',
        // 'raw_material' => 'integer',
        'products' => 'array', // This assumes you'll store JSON in the products column
        'product_id' => 'array',
        'raw_material' => 'array',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function location()
    {
        return $this->belongsTo(BusinessLocation::class);
    }
    
}