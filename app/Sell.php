<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Sell extends Model
{
    protected $fillable = [
        'location_id',
        'contact_id',
        'pay_term_number',
        'pay_term_type',
        'transaction_date',
        'status',
        'invoice_scheme_id',
        'invoice_no',
        'shipping_details',
        'shipping_address',
        'shipping_charges',
        'shipping_status',
        'delivered_to',
        'delivery_person',
        'final_total'
    ];

    // Relationship with sell items
    public function sellItems()
    {
        return $this->hasMany(SellItem::class);
    }
}