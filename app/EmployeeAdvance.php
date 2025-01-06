<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EmployeeAdvance extends Model
{
    protected $table = 'employee_advance';
    
    protected $fillable = [
        // 'empid',
        'date',
        'refund',
        'refund_date',
        'refund_amount',
        'balance',
        'user_id'
    ];

    // Define relationship with User model
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id'); // Adjust if your foreign key is different
    }
}