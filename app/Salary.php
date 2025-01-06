<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Salary extends Model
{
    protected $fillable = [
        'user_id', // Keep this as is, it will now reference users table
        'salary_date', 
        'basic_salary', 
        'deduction', 
        'tax_deduction', 
        'net_salary', 
        'bank_account_number', 
        'payment_mode', 
        'salary_payment_mode'
    ];

    // Relationship with User model
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}