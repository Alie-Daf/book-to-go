<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HostVerification extends Model
{
    protected $primaryKey = 'host_verification_id';
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = ['submitted_at' => 'datetime'];

    public function host() { return $this->belongsTo(User::class, 'host_id', 'user_id'); }
}