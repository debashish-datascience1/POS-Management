<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EmployeeAdvance extends Model
{
    // Specify the table name if it's different from the default plural form
    protected $table = 'employee_advance';

    // Fillable fields for mass assignment
    protected $fillable = [
        'empid',  // Note: using empid instead of employee_id based on your table schema
        'date', 
        'refund', 
        'refund_date', 
        'refund_amount', 
        'balance'
    ];

    // Define the relationship with Employee model
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'empid');
    }
}