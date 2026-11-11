<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $primaryKey = 'review_id';
    protected $guarded = [];

    public function guest() { return $this->belongsTo(User::class, 'guest_id', 'user_id'); }
    public function unit() { return $this->belongsTo(Unit::class, 'unit_id', 'unit_id'); }
}