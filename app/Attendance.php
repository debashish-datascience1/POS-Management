<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    // Define the table if it's different from the default naming convention
    protected $table = 'attendance';  // Name of the table in the database

    // Define the fillable columns for mass assignment
    protected $fillable = [
        'employee_id',
        'employee_name',
        'date',
        'check_in_time',
        'check_out_time',
        'leave_type',
        'total_hours_worked',
        'overtime_hours'
    ];

    // If you want to use timestamps (created_at, updated_at), you can leave this as true.
    public $timestamps = true;

    // Optionally, if you want to define a relationship with Employee, add this method:
    // public function employee()
    // {
    //     return $this->belongsTo(Employee::class);
    // }
}
