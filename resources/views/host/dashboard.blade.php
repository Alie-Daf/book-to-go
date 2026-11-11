@extends('layouts.app')
@section('title', 'Dashboard')
@section('nav', 'host.dashboard')
@section('topbar') @include('partials.topbar') @endsection

@section('content')
    <h1 class="page-title">Dashboard</h1>
    <p class="page-sub">Overview of your units and bookings</p>

    <div class="stat-grid">
        <div class="stat-card"><div class="stat-label">Total Units</div><div class="stat-value">{{ $totalUnits }}</div></div>
        <div class="stat-card"><div class="stat-label">Active Bookings</div><div class="stat-value">{{ $activeBookings }}</div></div>
        <div class="stat-card"><div class="stat-label">Total Earnings</div><div class="stat-value">&#8369;{{ number_format($totalEarnings) }}</div></div>
        <div class="stat-card"><div class="stat-label">Pending</div><div class="stat-value">{{ $pending }}</div></div>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-5 mb-3">
        <h2 class="section-title mb-0">Recent Bookings</h2>
        <a href="{{ route('host.guests') }}" class="text-muted">View All</a>
    </div>

    <div class="table-card">
        <table class="table mb-0">
            <thead><tr><th>Guest Name</th><th>Unit</th><th>Check-in and Check-out</th><th>Status</th></tr></thead>
            <tbody>
                @forelse ($recent as $booking)
                    <tr>
                        <td>{{ $booking->guest->full_name }}</td>
                        <td>{{ $booking->unit->title }}</td>
                        <td>{{ $booking->check_in_date->format('M j') }} - {{ $booking->check_out_date->format('M j, Y') }}</td>
                        <td><span class="status-pill status-{{ $booking->booking_status }}">{{ $booking->booking_status }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">No bookings yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection