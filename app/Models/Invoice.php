<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Invoice extends Model
{
    protected $primaryKey = 'invoice_id';
    const UPDATED_AT = null;            // the table only has created_at
    protected $guarded = [];
    protected $casts = ['due_date' => 'date', 'verified_at' => 'datetime'];

    public function host() { return $this->belongsTo(User::class, 'host_id', 'user_id'); }
    public function booking() { return $this->belongsTo(Booking::class, 'booking_id', 'booking_id'); }
    public function unitImagePack() { return $this->hasOne(UnitImagePack::class, 'invoice_id', 'invoice_id'); }

    // CSS class for the coloured status badge
    public function getStatusClassAttribute() { return 'inv-'.Str::slug($this->status); }
}