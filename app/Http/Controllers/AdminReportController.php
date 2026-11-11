<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Unit;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminReportController extends Controller
{
    public function index(Request $request)
    {
        [$from, $to] = $this->dateRange($request);
        $from = Carbon::parse($from ?? now()->subMonths(5)->startOfMonth());   // default: last 6 months
        $to = Carbon::parse($to ?? now()->endOfMonth());
        $type = $request->input('type', 'month');

        $bookings = Booking::with('unit')->where('booking_status', 'Confirmed')
            ->whereBetween('check_in_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('check_in_date')->get();

        // chart data: revenue per month, or revenue per unit
        $chart = $type === 'unit'
            ? $bookings->groupBy(fn ($b) => $b->unit->title)->map->sum('total_amount')
            : $bookings->groupBy(fn ($b) => $b->check_in_date->format('M Y'))->map->sum('total_amount');

        // occupancy = booked nights / (live units x days in range)
        $days = (int) $from->diffInDays($to) + 1;
        $units = Unit::where('listing_status', 'Approved')->count();
        $occupancy = $units ? min(100, round($bookings->sum('nights') / ($units * $days) * 100)) : 0;

        return view('admin.reports', [
            'totalBookings' => $bookings->count(),
            'revenue' => $bookings->sum('total_amount'),
            'occupancy' => $occupancy,
            'chart' => $chart,
            'type' => $type,
            'platformIncome' => Invoice::where('status', 'Paid')
                ->whereBetween('verified_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])->sum('amount'),
        ]);
    }
}