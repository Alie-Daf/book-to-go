<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\AuditLog;
use App\Notifications\AppNotice;
use Illuminate\Http\Request;

class AdminBookingController extends Controller
{
    public function index(Request $request)
    {
        [$from, $to] = $this->dateRange($request);

        $bookings = Booking::with(['guest', 'unit'])
            ->when($request->status, fn ($q, $v) => $q->where('booking_status', $v))
            ->when($from, fn ($q, $v) => $q->where('check_in_date', '>=', $v))
            ->when($to, fn ($q, $v) => $q->where('check_in_date', '<=', $v))
            ->latest('requested_at')->get();

        return $request->ajax()
            ? view('admin.bookings-table', compact('bookings'))
            : view('admin.bookings', compact('bookings'));
    }
    
    // Nudge a host who has not confirmed a payment yet
    public function remind(Booking $booking)
    {
        abort_unless($booking->booking_status === 'Paid', 422, 'This booking is not waiting for the host.');

        $booking->unit->host->notify(new AppNotice(
            "Reminder: please confirm the payment for booking {$booking->booking_reference}.",
            route('host.bookings.show', $booking, false)
        ));
        AuditLog::record('Reminded host to confirm payment', $booking);

        return ['message' => 'Reminder sent to the host.'];
    }
}