<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnitImage extends Model
{
    protected $primaryKey = 'image_id';
    public $timestamps = false;
    protected $guarded = [];
}