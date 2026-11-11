<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnitImagePack extends Model
{
    protected $primaryKey = 'unit_pack_id';
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = ['start_date' => 'date', 'end_date' => 'date'];

    public function pack() { return $this->belongsTo(ImagePack::class, 'pack_id', 'pack_id'); }
}