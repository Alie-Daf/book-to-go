<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use App\Services\BillingService;

class HostBookingController extends Controller
{
    // Guest Information page: who booked which unit
    public function index(Request $request)
    {
        $bookings = $this->mine()->with(['guest', 'unit'])
            ->when($request->q, fn ($q, $v) => $q->whereHas('guest', fn ($g) => $g->search($v)))
            ->latest('requested_at')->get();

        return $request->ajax()
            ? view('host.guests-table', compact('bookings'))
            : view('host.guests', compact('bookings'));
    }

    // Booking details: guest authorization form, proof of payment and actions
    public function show(Booking $booking)
    {
        $this->own($booking);

        return view('host.booking', ['booking' => $booking->load('guest', 'unit')]);
    }

    // The host confirms the payment. The admin then approves the booking.
    // EVENT: Confirm Payment Received. Confirms, saves the commission, creates the passcode and the invoice.
    public function confirmPayment(Booking $booking)
    {
        $this->own($booking);

        $invoice = BillingService::confirmBooking($booking);

        return ['message' => 'Payment confirmed. A commission invoice of ₱'.number_format($invoice->amount, 2).' was created (see Billing).'];
    }

    // The host cancels a confirmed booking: the dates open again and the commission invoice is cancelled
    public function cancel(Booking $booking)
    {
        $this->own($booking);
        abort_unless($booking->booking_status === 'Confirmed', 422, 'Only confirmed bookings can be cancelled.');

        BillingService::cancelBooking($booking);

        return ['message' => 'Booking cancelled. Its commission invoice was cancelled too.'];
    }

    public function generatePasscode(Booking $booking)
    {
        $this->own($booking);
        abort_unless($booking->booking_status === 'Confirmed', 422, 'The booking must be confirmed first.');

        $booking->update(['door_passcode' => (string) random_int(100000, 999999)]);

        return ['message' => 'Door passcode generated.'];
    }

    private function mine()
    {
        return Booking::whereHas('unit', fn ($q) => $q->where('host_id', auth()->id()));
    }

    private function own(Booking $booking): void
    {
        abort_unless($booking->unit->host_id == auth()->id(), 403);
    }
}