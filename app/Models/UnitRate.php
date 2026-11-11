<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnitRate extends Model
{
    protected $primaryKey = 'rate_id';
    public $timestamps = false;
    protected $guarded = [];
}