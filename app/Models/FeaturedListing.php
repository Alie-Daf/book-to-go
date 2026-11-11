<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeaturedListing extends Model
{
    protected $primaryKey = 'featured_id';
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = ['start_date' => 'date', 'end_date' => 'date'];

    private static ?array $activeIds = null;   // remembered for the current page load

    public function unit() { return $this->belongsTo(Unit::class, 'unit_id', 'unit_id'); }
    public function invoice() { return $this->belongsTo(Invoice::class, 'invoice_id', 'invoice_id'); }

    // Units guests may see as Featured: Active, inside the dates, unit Approved and host NOT on hold
    public static function activeUnitIds(): array
    {
        return static::$activeIds ??= static::where('featured_status', 'Active')
            ->where('start_date', '<=', today()->toDateString())
            ->where('end_date', '>=', today()->toDateString())
            ->whereHas('unit', fn ($q) => $q->where('listing_status', 'Approved')
                ->whereHas('host', fn ($h) => $h->where('account_status', 'Active')))
            ->pluck('unit_id')->all();
    }

    public static function isFeatured(int $unitId): bool
    {
        return in_array($unitId, static::activeUnitIds());
    }
}