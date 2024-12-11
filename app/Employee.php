<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    // Specify the table name if it's different from the default (plural of model name)
    protected $table = 'employees';

    // Fillable fields for mass assignment
    protected $fillable = [
        'name',
        'age',
        'idprove',
        'phone',
        'gender',
        'address',
        'file_url'
    ];

    // Optional: If you want to cast certain attributes
    protected $casts = [
        'age' => 'integer'
    ];

    // Optional: If you want to hide certain fields from JSON serialization
    protected $hidden = [];
}