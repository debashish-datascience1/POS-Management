<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PackingStock extends Model
{
    protected $table = 'packing_stock';
    
    protected $fillable = ['location_id', 'total', 'production_unit_id','date'];

    public function location()
    {
        return $this->belongsTo(BusinessLocation::class, 'location_id');
    }
}