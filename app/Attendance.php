<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    // Specify the table name (if it differs from the plural form of the model)
    protected $table = 'attendances';

    // Define the primary key (optional, as it's the default 'id')
    protected $primaryKey = 'id';

    // Specify the fields that can be mass-assigned
    protected $fillable = [
        'user_id', 
        'select_year', 
        'select_month', 
        'day', 
        'status',
    ];

    // The "timestamps" field is true by default, so we don't need to define it unless we're changing it
    public $timestamps = true;

    /**
     * Define a relationship: Attendance belongs to an Employee
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Define a relationship: Attendance can have multiple records for the same employee and month
     */
    public function attendanceDetails()
    {
        return $this->hasMany(Attendance::class, 'user_id')
                    ->where('select_year', $this->select_year)
                    ->where('select_month', $this->select_month);
    }

    public function getStatusAttribute($value)
    {
        // Normalize the status to lowercase
        return strtolower(trim($value));
    }
}
