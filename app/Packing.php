<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Packing extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'temperature',
        'product_temperature', 
        'quantity',    
        'mix',
        'jar',
        'packet',
        'total',
        'grand_total',
        'date',
        'location_id'
    ];

    protected $dates = ['date'];
    protected $casts = [
        'jar' => 'array',
        'temperature' => 'array',
        'packet' => 'array',
        'date' => 'date',
        'quantity' => 'array',
        'mix' => 'array',
        'total' => 'array',
        'grand_total' => 'decimal:2',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function temperature()
    {
        return $this->belongsTo(Temperature::class, 'temperature', 'temperature');
    }

    public function setJarAttribute($value)
    {
        $this->attributes['jar'] = is_array($value) ? json_encode($value) : $value;
    }

    public function getJarAttribute($value)
    {
        return json_decode($value, true) ?? [];
    }

    public function setPacketAttribute($value)
    {
        $this->attributes['packet'] = is_array($value) ? json_encode($value) : $value;
    }

    public function getPacketAttribute($value)
    {
        return json_decode($value, true) ?? [];
    }
}