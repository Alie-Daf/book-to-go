@extends('layouts.app')
@section('title', 'Booking')
@section('nav', 'home')
@section('topbar') @include('reservation.steps', ['step' => 3]) @endsection

@section('content')
    <div class="card-box done-card mx-auto text-center">
        <i class="bi bi-check-circle-fill done-icon"></i>
        <h1 class="page-title mb-1">{{ $booking->booking_status === 'Confirmed' ? 'Booking Confirmed!' : 'Booking Received!' }}</h1>
        <p class="text-muted">Reference: {{ $booking->booking_reference }}</p>

        <div class="d-flex align-items-center gap-3 text-start my-4">
            <div class="photo summary-photo rounded-3">
                <img src="{{ $booking->unit->cover }}" alt="" onerror="this.remove()"><i class="bi bi-image"></i>
            </div>
            <div>
                <h2 class="section-title mb-1">{{ $booking->unit->title }}</h2>
                <div class="text-muted">{{ $booking->check_in_date->format('M j') }} - {{ $booking->check_out_date->format('M j, Y') }} &middot; {{ $booking->nights }} night(s)</div>
                <div class="fs-4 fw-bold">&#8369;{{ number_format($booking->total_amount) }} <small class="fs-6 fw-normal text-muted">/ Total</small></div>
            </div>
        </div>

        <div class="d-flex gap-3 justify-content-center d-print-none">
            <a href="{{ route('bookings.show', $booking) }}" class="btn btn-outline-primary px-4">View booking details</a>
            <button type="button" class="btn btn-outline-primary px-4" onclick="window.print()">Download receipt</button>
        </div>
    </div>
@endsection