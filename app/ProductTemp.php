<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductTemp extends Model
{
    use HasFactory;

    protected $table = 'product_temp';

    protected $fillable = [
        'business_id',
        'location_id',
        'date',
        'temperature',
        'quantity',
        'product_output',
        'packing_stock_id'
    ];

    protected $casts = [
        'date' => 'date',
        'temperatures' => 'array',
        'quantities' => 'array',
        'product_output' => 'decimal:4'
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function location()
    {
        return $this->belongsTo(BusinessLocation::class, 'location_id');
    }
}