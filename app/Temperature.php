<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Temperature extends Model
{
    protected $table = 'temperature';
    protected $fillable = ['temperature', 'temp_quantity'];
}