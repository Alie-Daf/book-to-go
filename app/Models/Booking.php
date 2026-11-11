<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $primaryKey = 'booking_id';
    public $timestamps = false;          // the table uses requested_at instead
    protected $guarded = [];
    protected $casts = ['check_in_date' => 'date', 'check_out_date' => 'date', 'requested_at' => 'datetime', 'paid_at' => 'datetime'];

    public function guest() { return $this->belongsTo(User::class, 'guest_id', 'user_id'); }
    public function unit() { return $this->belongsTo(Unit::class, 'unit_id', 'unit_id'); }
    public function review() { return $this->hasOne(Review::class, 'booking_id', 'booking_id'); }
    public function getNightsAttribute() { return (int) $this->check_in_date->diffInDays($this->check_out_date); }

    // Tabs on the My Bookings page (there are no cancelled bookings)
    // Tabs on the My Bookings page
    public function scopeTab($query, $tab)
    {
        return match ($tab) {
            'upcoming' => $query->where('booking_status', '!=', 'Cancelled')->where('check_out_date', '>=', today()),
            'past' => $query->where('booking_status', '!=', 'Cancelled')->where('check_out_date', '<', today()),
            'cancelled' => $query->where('booking_status', 'Cancelled'),
            default => $query,
        };
    }

    // Reviews are only allowed after a confirmed stay is over
    public function scopeReviewable($query)
    {
        return $query->where('booking_status', 'Confirmed')->where('check_out_date', '<', today())->doesntHave('review');
    }
}