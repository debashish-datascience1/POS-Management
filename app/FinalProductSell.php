<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FinalProductSell extends Model
{
    protected $table = 'final_product_sells';

    protected $fillable = [
        'business_id',
        'location_id',
        'contact_id',
        'date',
        'grand_total',
        'created_by'
    ];

    protected $casts = [
        'grand_total' => 'decimal:4',
        'date' => 'date'
    ];

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function location()
    {
        return $this->belongsTo(BusinessLocation::class, 'location_id');
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sell_lines()
    {
        return $this->hasMany(FinalProductSellLine::class);
    }
}