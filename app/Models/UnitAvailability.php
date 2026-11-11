<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnitAvailability extends Model
{
    protected $table = 'unit_availability';
    protected $primaryKey = 'availability_id';
    public $timestamps = false;
    protected $guarded = [];
}