<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $primaryKey = 'report_id';
    protected $guarded = [];

    public function reporter() { return $this->belongsTo(User::class, 'reporter_id', 'user_id'); }
    public function review() { return $this->belongsTo(Review::class, 'review_id', 'review_id'); }
    public function unit() { return $this->belongsTo(Unit::class, 'unit_id', 'unit_id'); }
}