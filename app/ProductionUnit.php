<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionUnit extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'date',
        'raw_material',
        'products',
    ];

    protected $casts = [
        'date' => 'date',
        'raw_material' => 'integer',
        'products' => 'array', // This assumes you'll store JSON in the products column
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    
}