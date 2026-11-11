<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    protected $primaryKey = 'unit_id';
    protected $guarded = [];

    // ---------- relationships ----------
    public function host() { return $this->belongsTo(User::class, 'host_id', 'user_id'); }
    public function images() { return $this->hasMany(UnitImage::class, 'unit_id', 'unit_id')->orderBy('display_order'); }
    public function rates() { return $this->hasMany(UnitRate::class, 'unit_id', 'unit_id'); }
    public function availability() { return $this->hasMany(UnitAvailability::class, 'unit_id', 'unit_id'); }
    public function reviews() { return $this->hasMany(Review::class, 'unit_id', 'unit_id'); }
    public function amenities() { return $this->belongsToMany(Amenity::class, 'unit_amenities', 'unit_id', 'amenity_id'); }

    // ---------- small helpers for the views ----------
    public function getCoverAttribute() { return $this->images->first()?->image_url; }
    public function getLocationAttribute() { return "{$this->city}, {$this->province}"; }
    public function getStartingRateAttribute() { return $this->rates->min('nightly_rate'); }
    public function getRatingLabelAttribute() { return $this->reviews_avg_rating ? number_format($this->reviews_avg_rating, 1) : 'New'; }
    public function getCheckInLabelAttribute() { return Carbon::parse($this->check_in_time)->format('g:i A'); }
    public function getCheckOutLabelAttribute() { return Carbon::parse($this->check_out_time)->format('g:i A'); }

    // ---------- browse and search: location, category, amenities, guests, price ----------
    public function scopeSearch($query, array $f)
    {
        return $query
            ->when($f['q'] ?? null, fn ($q, $v) => $q->where('title', 'like', "%$v%"))
            ->when($f['location'] ?? null, fn ($q, $v) => $q->where(fn ($w) => $w
                ->where('city', 'like', "%$v%")->orWhere('province', 'like', "%$v%")->orWhere('address_line', 'like', "%$v%")))
            ->when($f['categories'] ?? null, fn ($q, $v) => $q->whereIn('category', (array) $v))
            ->when($f['amenities'] ?? null, function ($q, $v) {
                foreach ((array) $v as $name) {
                    $q->whereHas('amenities', fn ($a) => $a->where('amenity_name', $name));
                }
            })
            ->when($f['guests'] ?? null, fn ($q, $v) => $q->where('max_guests', '>=', $v))
            ->when($f['max_price'] ?? null, fn ($q, $v) => $q->whereHas('rates', fn ($r) => $r->where('nightly_rate', '<=', $v)));
    }

    // ---------- pax variations and rates ----------
    public function rateFor(int $guests): float
    {
        $rates = $this->rates->sortBy('guest_count');
        $rate = $rates->first(fn ($r) => $r->guest_count >= $guests) ?? $rates->last();

        return (float) $rate->nightly_rate;
    }

    // ---------- calendar: a stay is free when no Booked/Blocked range overlaps its nights ----------
    public function isAvailable(string $checkIn, string $checkOut): bool
    {
        $lastNight = Carbon::parse($checkOut)->subDay()->toDateString();

        return ! $this->availability()
            ->whereIn('availability_status', ['Booked', 'Blocked'])
            ->where('start_date', '<=', $lastNight)
            ->where('end_date', '>=', $checkIn)
            ->exists();
    }

    public function quote(string $checkIn, string $checkOut, int $guests): array
    {
        $nights = (int) Carbon::parse($checkIn)->diffInDays($checkOut);
        $rate = $this->rateFor($guests);

        return [
            'nights' => $nights,
            'rate' => $rate,
            'total' => $nights * $rate,
            'available' => $this->isAvailable($checkIn, $checkOut),
        ];
    }

        public function imagePacks() { return $this->hasMany(UnitImagePack::class, 'unit_id', 'unit_id'); }

    // Images allowed: the free limit, or the biggest pack that is Paid and not expired
    // (the pack's dates are only filled in once the invoice is Paid)
    public function imageLimit(): int
    {
        $free = (int) FeeSetting::get('free_image_limit', 7);

        $pack = $this->imagePacks()->whereNotNull('end_date')
            ->where('end_date', '>=', today()->toDateString())
            ->with('pack')->get()->max(fn ($p) => $p->pack->max_images);

        return max($free, (int) $pack);
    }

    // Guests only see the images inside the limit. Extra images are hidden, not deleted.
    public function getVisibleImagesAttribute() { return $this->images->take($this->imageLimit()); }

        // Guests can only book approved units whose host is not on hold
    public function getIsBookableAttribute()
    {
        return $this->listing_status === 'Approved' && $this->host->account_status === 'Active';
    }
}