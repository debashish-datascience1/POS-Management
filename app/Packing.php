<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Packing extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'product_id',
        'product_output',
        'mix',
        'jar',
        'packet',
        'total',
        'grand_total',
        'date',
        'location_id'
    ];

    protected $casts = [
        'jar' => 'array',
        'packet' => 'array',
        'date' => 'date',
        'product_output' => 'integer',
        'mix' => 'integer',
        'total' => 'float',
        'grand_total' => 'float',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function setJarAttribute($value)
    {
        $this->attributes['jar'] = json_encode($value);
    }

    public function getJarAttribute($value)
    {
        return json_decode($value, true) ?? [];
    }

    public function setPacketAttribute($value)
    {
        $this->attributes['packet'] = json_encode($value);
    }

    public function getPacketAttribute($value)
    {
        return json_decode($value, true) ?? [];
    }
}