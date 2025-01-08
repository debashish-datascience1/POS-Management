<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalaryPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',  // Changed from employee_id to user_id to match relationship
        'amount',
        'payment_date',
    ];

    // Update the relationship to point to User model
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
