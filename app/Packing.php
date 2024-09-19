<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Packing extends Model
{
    use HasFactory;

    protected $fillable = ['business_id', 'product_id', 'product_output', 'mix', 'packing', 'total', 'date'];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function setPackingAttribute($value)
    {
        $this->attributes['packing'] = is_array($value) ? implode(',', $value) : $value;
    }

    public function getPackingAttribute($value)
    {
        return explode(',', $value);
    }
}