<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Amenity extends Model
{
    protected $primaryKey = 'amenity_id';
    public $timestamps = false;
    protected $guarded = [];
}