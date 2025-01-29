<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FinalProductSellLine extends Model
{
    protected $table = 'final_product_sell_lines';

    protected $fillable = [
        'final_product_sell_id',
        'product_temperature',
        'quantity',
        'amount'
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'amount' => 'decimal:4'
    ];

    public function final_product_sell()
    {
        return $this->belongsTo(FinalProductSell::class);
    }
}