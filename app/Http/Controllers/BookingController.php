<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use App\Notifications\AppNotice;
use App\Services\BillingService;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    // My Bookings: All / Upcoming / Past
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'all');

        $bookings = $request->user()->bookings()->with('unit.images')->tab($tab)->latest('check_in_date')->get();

        return view('bookings.index', compact('bookings', 'tab'));
    }

    // Step 3: Done
    public function done(Booking $booking)
    {
        $this->ownBooking($booking);

        return view('bookings.done', ['booking' => $booking->load('unit.images')]);
    }

    // Booking details: payment instructions, status and guest pass
public function show(Booking $booking)
{
    $unit = $booking->unit;
    return view('bookings.show', compact('booking', 'unit'));
}

    // The guest pays the host directly (GCash) and uploads the proof of payment
    // The guest pays the host directly, then sends the payment reference number and a screenshot
    public function uploadProof(Request $request, Booking $booking)
    {
        $this->ownBooking($booking);
        abort_unless($booking->booking_status === 'Pending', 422);

        $request->merge(['payment_reference' => strtoupper(trim((string) $request->payment_reference))]);
        $request->validate([
            'payment_reference' => 'required|alpha_num|between:6,40|unique:bookings,payment_reference',
            'proof' => 'required|file|mimes:jpg,jpeg,png|max:2048',
        ], ['payment_reference.unique' => 'This payment reference was already used for another booking.']);

        // the same screenshot cannot be used for two bookings
        $hash = hash_file('sha256', $request->file('proof')->getRealPath());
        if (Booking::where('payment_proof_hash', $hash)->exists()) {
            throw ValidationException::withMessages(['proof' => 'This screenshot was already used for another booking.']);
        }

        $booking->update([
            'payment_proof' => $request->file('proof')->store('payment-proofs'),   // private storage
            'payment_reference' => $request->payment_reference,
            'payment_proof_hash' => $hash,
            'booking_status' => 'Paid',
            'paid_at' => now(),
        ]);

        $booking->unit->host->notify(new AppNotice(
            "{$request->user()->full_name} uploaded a proof of payment for {$booking->booking_reference}.",
            route('host.bookings.show', $booking, false)
        ));

        return ['message' => 'Proof uploaded. Waiting for the host to confirm your payment.'];
    }

    // A guest may cancel only before paying. After paying, the no-cancellation, no-refund policy applies.
    public function cancel(Booking $booking)
    {
        $this->ownBooking($booking);
        abort_unless($booking->booking_status === 'Pending', 422, 'A paid booking cannot be cancelled (no-cancellation, no-refund policy).');

        BillingService::cancelBooking($booking, 'guest');

        return ['message' => 'Booking cancelled. The dates are open again.'];
    }

    private function ownBooking(Booking $booking): void
    {
        abort_unless($booking->guest_id == auth()->id(), 403);
    }
}