<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// Booking steps: 1 Reservation -> 2 Guests -> 3 Done
class ReservationController extends Controller
{
    // Step 1: dates and number of guests
    public function details(Request $request, Unit $unit)
    {
        if ($redirect = $this->guard($request, $unit)) {
            return $redirect;
        }

        return view('reservation.details', ['unit' => $unit->load('images')]);
    }

    public function saveDetails(Request $request, Unit $unit)
    {
        if ($redirect = $this->guard($request, $unit)) {
            return $redirect;
        }

        $data = $request->validate([
            'check_in_date' => 'required|date|after_or_equal:today',
            'check_out_date' => 'required|date|after:check_in_date',
            'guest_count' => "required|integer|min:1|max:{$unit->max_guests}",
            'policy' => 'accepted',
        ], ['policy.accepted' => 'You must agree to the no-cancellation, no-refund policy.']);

        if (! $unit->isAvailable($data['check_in_date'], $data['check_out_date'])) {
            return back()->withInput()->withErrors(['check_in_date' => 'Those dates are not available.']);
        }

        session(['reservation' => $data + ['unit_id' => $unit->unit_id]]);

        return redirect()->route('reservation.guests', $unit);
    }

    // Step 2: guest authorization form
    public function guests(Request $request, Unit $unit)
    {
        if ($redirect = $this->guard($request, $unit)) {
            return $redirect;
        }

        $draft = session('reservation');

        if (! $draft || $draft['unit_id'] != $unit->unit_id) {
            return redirect()->route('reservation.details', $unit);
        }

        return view('reservation.guests', compact('unit', 'draft'));
    }

    public function store(Request $request, Unit $unit)
    {
        if ($redirect = $this->guard($request, $unit)) {
            return $redirect;
        }

        $draft = session('reservation');

        if (! $draft || $draft['unit_id'] != $unit->unit_id) {
            return redirect()->route('reservation.details', $unit);
        }

        $data = $request->validate([
            'authorized_name' => 'required|max:255',
            'authorized_contact' => 'required|max:30',
            'authorized_email' => 'required|email',
            'agree' => 'accepted',
        ], ['agree.accepted' => 'You must agree to the terms and conditions.']);

        $booking = DB::transaction(function () use ($unit, $draft, $data, $request) {
            // lock the unit so two guests cannot book the same dates
            Unit::whereKey($unit->unit_id)->lockForUpdate()->first();

            if (! $unit->isAvailable($draft['check_in_date'], $draft['check_out_date'])) {
                return null;
            }

            $quote = $unit->load('rates')->quote($draft['check_in_date'], $draft['check_out_date'], $draft['guest_count']);

            $booking = Booking::create([
                'booking_reference' => 'BTG-'.now()->format('ymd').'-'.strtoupper(Str::random(4)),
                'guest_id' => $request->user()->user_id,
                'unit_id' => $unit->unit_id,
                'check_in_date' => $draft['check_in_date'],
                'check_out_date' => $draft['check_out_date'],
                'guest_count' => $draft['guest_count'],
                'total_amount' => $quote['total'],
                'authorized_name' => $data['authorized_name'],
                'authorized_contact' => $data['authorized_contact'],
                'authorized_email' => $data['authorized_email'],
                'requested_at' => now(),   // set by the app so hold hours use the same clock as the rest of the system
            ]);

            // booked dates are blocked automatically on the calendar
            $unit->availability()->create([
                'start_date' => $draft['check_in_date'],
                'end_date' => Carbon::parse($draft['check_out_date'])->subDay()->toDateString(),
                'availability_status' => 'Booked',
                'notes' => $booking->booking_reference,
            ]);

            return $booking;
        });

        if (! $booking) {
            return redirect()->route('reservation.details', $unit)
                ->withErrors(['check_in_date' => 'Sorry, those dates were just taken.']);
        }

        session()->forget('reservation');

        return redirect()->route('bookings.done', $booking);   // Step 3
    }

    // Who may book? Returns a redirect when the guest or the unit is not allowed, otherwise null.
    private function guard(Request $request, Unit $unit)
    {
        // a host on hold cannot confirm new bookings, so guests must not pay him
        if (! $unit->is_bookable) {
            return redirect()->route('units.show', $unit)->with('warning', 'This unit is temporarily unavailable for new bookings.');
        }

        // the valid ID must be APPROVED by the admin (it is the age verification)
        $id = $request->user()->documents()->where('document_type', 'Valid ID')->latest('document_id')->first();

        if (! $id) {
            return redirect()->route('profile')->with('warning', 'Please upload a valid ID before booking a unit.');
        }
        if ($id->verification_status !== 'Approved') {
            return redirect()->route('profile')->with('warning', $id->verification_status === 'Rejected'
                ? 'Your valid ID was rejected. Please upload a clearer one.'
                : 'Your valid ID is waiting for the admin to approve it.');
        }

        return null;
    }
}