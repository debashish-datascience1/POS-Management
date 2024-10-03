<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PackingStock extends Model
{
    protected $table = 'packing_stock';
    protected $fillable = ['location_id', 'total'];
}