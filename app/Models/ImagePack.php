<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImagePack extends Model
{
    protected $primaryKey = 'pack_id';
    public $timestamps = false;
    protected $guarded = [];
}