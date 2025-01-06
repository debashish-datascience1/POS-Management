<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Balance extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'business_id',
        'date',
        'raw_material',
        'product_id',
        'total_quantity',
        'location_id',
        'temperature',
        'temp_quantity'
    ];

    protected $casts = [
        'date' => 'date',
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