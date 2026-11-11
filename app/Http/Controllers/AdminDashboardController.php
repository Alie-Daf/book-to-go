<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\FeaturedListing;
use App\Models\Invoice;
use App\Models\Unit;
use App\Models\User;
use App\Models\FeeSetting;
use App\Services\BillingService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    private const STREAMS = ['Commission', 'Image Pack', 'Featured', 'Unit Slot'];

    public function index(Request $request)
    {
        BillingService::checkOverdue();   // event: the dashboard was opened

        // bookings per month for the last 6 months
        $months = collect(range(5, 0))->map(fn ($i) => now()->startOfMonth()->subMonths($i));

        // month filter for the revenue section
        $month = preg_match('/^\d{4}-\d{2}$/', (string) $request->month) ? $request->month : now()->format('Y-m');
        $start = Carbon::parse($month.'-01')->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $paid = fn () => Invoice::where('status', 'Paid');
        $perStream = $paid()->whereBetween('verified_at', [$start, $end])
            ->selectRaw('invoice_type, sum(amount) as total')->groupBy('invoice_type')->pluck('total', 'invoice_type');

        return view('admin.dashboard', [
            'totalGuests' => User::where('role', 'Guest')->count(),
            'totalHosts' => User::where('role', 'Host')->count(),
            'totalUnits' => Unit::count(),
            'totalBookings' => Booking::count(),
            'labels' => $months->map(fn ($m) => $m->format('M')),
            'values' => $months->map(fn ($m) => Booking::whereBetween('requested_at', [$m, $m->copy()->endOfMonth()])->count()),
            'byStatus' => Booking::selectRaw('booking_status, count(*) as total')->groupBy('booking_status')->pluck('total', 'booking_status'),

            // revenue
            'month' => $month,
            'commissionMonth' => $paid()->where('invoice_type', 'Commission')->whereBetween('verified_at', [$start, $end])->sum('amount'),
            'commissionAll' => $paid()->where('invoice_type', 'Commission')->sum('amount'),
            'unpaidTotal' => Invoice::whereIn('status', ['Unpaid', 'For Verification'])->sum('amount'),
            'overdueTotal' => Invoice::where('status', 'Overdue')->sum('amount'),
            'streamLabels' => self::STREAMS,
            'streamValues' => collect(self::STREAMS)->map(fn ($type) => (float) ($perStream[$type] ?? 0)),
            'activeFeatured' => FeaturedListing::where('featured_status', 'Active')->count(),
            'onHold' => User::where('role', 'Host')->where('account_status', 'Hold')->get(),

            // bookings the guest paid for but the host has not confirmed (the host may be hiding the sale to skip the commission)
            'staleBookings' => Booking::with(['guest', 'unit.host'])->where('booking_status', 'Paid')
                ->whereRaw('coalesce(paid_at, requested_at) < ?', [now()->subHours((int) FeeSetting::get('stale_paid_hours', 48))])
                ->get(),
        ]);
    }
}