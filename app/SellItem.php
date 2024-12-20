<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SellItem extends Model
{
    protected $fillable = [
        'sell_id',
        'item_type',
        'item_name',
        'quantity',
        'price'
    ];

    public function sell()
    {
        return $this->belongsTo(Sell::class);
    }
}