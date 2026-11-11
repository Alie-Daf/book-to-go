<?php

namespace App\Http\Controllers;

use App\Models\Amenity;
use App\Models\Review;
use App\Models\Unit;
use App\Models\Report;
use App\Models\FeaturedListing;
use App\Models\FeeSetting;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    // Browse and search units. AJAX requests only get the results part.
    public function index(Request $request)
    {
        $base = Unit::with(['images', 'rates'])
            ->withAvg('reviews', 'rating')
            ->where('listing_status', 'Approved')
            ->search($request->all());

        // Featured units that match the search go first (limited per results page)
        $featured = (clone $base)->whereIn('unit_id', FeaturedListing::activeUnitIds())
            ->take((int) FeeSetting::get('max_featured_per_page', 3))->get();

        $units = $featured->concat($base->whereNotIn('unit_id', $featured->pluck('unit_id'))->latest('unit_id')->get());

        $bookmarked = $request->user()->bookmarks()->pluck('units.unit_id')->all();

        if ($request->ajax()) {
            return view('units.results', compact('units', 'bookmarked'));
        }

        return view('units.index', [
            'units' => $units,
            'bookmarked' => $bookmarked,
            'categories' => ['Room', 'Studio', 'Apartment', 'Condo', 'House'],
            'amenities' => Amenity::whereIn('amenity_name', ['Wi-Fi', 'Parking', 'Pool'])->get(),
        ]);
    }

    public function show(Request $request, Unit $unit)
    {
        abort_unless($unit->listing_status === 'Approved', 404);

        $unit->load(['images', 'rates', 'amenities', 'host.verification', 'reviews.guest'])->loadAvg('reviews', 'rating');

        return view('units.show', [
            'unit' => $unit,
            'isBookmarked' => $request->user()->bookmarks()->where('units.unit_id', $unit->unit_id)->exists(),
            // a finished, confirmed stay that has no review yet
            'reviewable' => $request->user()->bookings()->where('unit_id', $unit->unit_id)->reviewable()->first(),
        ]);
    }

    // Booked and blocked date ranges: the calendar makes these dates unclickable
    public function availability(Unit $unit)
    {
        return $unit->availability()
            ->whereIn('availability_status', ['Booked', 'Blocked'])
            ->where('end_date', '>=', today())
            ->get(['start_date as from', 'end_date as to']);
    }

    // Live price estimate for the reservation form
    public function quote(Request $request, Unit $unit)
    {
        $data = $request->validate([
            'check_in_date' => 'required|date',
            'check_out_date' => 'required|date|after:check_in_date',
            'guest_count' => "required|integer|min:1|max:{$unit->max_guests}",
        ]);

        $unit->load('rates');

        return $unit->quote($data['check_in_date'], $data['check_out_date'], $data['guest_count']);
    }

    // Reviews and feedback (after the stay)
    public function review(Request $request, Unit $unit)
    {
        $data = $request->validate([
            'booking_id' => 'required|integer',
            'rating' => 'required|integer|between:1,5',
            'comment' => 'required|max:1000',
        ]);

        $booking = $request->user()->bookings()
            ->where('unit_id', $unit->unit_id)->reviewable()->findOrFail($data['booking_id']);

        Review::create([
            'booking_id' => $booking->booking_id,
            'guest_id' => $request->user()->user_id,
            'unit_id' => $unit->unit_id,
            'rating' => $data['rating'],
            'comment' => $data['comment'],
        ]);

        return ['message' => 'Thank you for your review!'];
    }
        // Report a fake or wrong listing (goes to the admin's Disputes page)
    public function report(Request $request, Unit $unit)
    {
        abort_unless($request->user()->bookings()->where('unit_id', $unit->unit_id)->exists(), 403, 'You can only report a unit you booked.');
        $data = $request->validate(['reason' => 'required|max:1000']);

        Report::create(['reporter_id' => $request->user()->user_id, 'unit_id' => $unit->unit_id, 'reason' => $data['reason']]);

        return ['message' => 'Thank you. The admin will look into this listing.'];
    }
}