<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;

class HostDashboardController extends Controller
{
    public function index(Request $request)
    {
        $host = $request->user();
        $bookings = fn () => Booking::whereHas('unit', fn ($q) => $q->where('host_id', $host->user_id));

        return view('host.dashboard', [
            'totalUnits' => $host->units()->count(),
            'activeBookings' => $bookings()->where('booking_status', 'Confirmed')->where('check_out_date', '>=', today())->count(),
            'totalEarnings' => $bookings()->where('booking_status', 'Confirmed')->sum('total_amount'),
            'pending' => $bookings()->whereIn('booking_status', ['Pending', 'Paid'])->count(),
            'recent' => $bookings()->with(['guest', 'unit'])->latest('requested_at')->take(5)->get(),
        ]);
    }
}