<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Salary extends Model
{
    protected $fillable = [
        'employee_id', 
        'salary_date', 
        'basic_salary', 
        'deduction', 
        'tax_deduction', 
        'net_salary', 
        'bank_account_number', 
        'payment_mode', 
        'salary_payment_mode'
    ];

    // Define the relationship with Employee
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
