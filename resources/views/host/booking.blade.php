@extends('layouts.app')
@section('title', 'Booking details')
@section('nav', 'host.guests')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title mb-1">Booking details</h1>
            <div class="text-muted">Reference {{ $booking->booking_reference }}</div>
        </div>
        <span class="status-pill status-{{ $booking->booking_status }}">{{ $booking->booking_status }}</span>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card-box mb-4">
                <h2 class="section-title">Summary</h2>
                <dl class="summary-list mb-0">
                    <dt>Unit</dt><dd>{{ $booking->unit->title }}</dd>
                    <dt>Guest</dt><dd>{{ $booking->guest->full_name }}</dd>
                    <dt>Check-in</dt><dd>{{ $booking->check_in_date->format('M j, Y') }}, {{ $booking->unit->check_in_label }}</dd>
                    <dt>Check-out</dt><dd>{{ $booking->check_out_date->format('M j, Y') }}, {{ $booking->unit->check_out_label }}</dd>
                    <dt>Guests</dt><dd>{{ $booking->guest_count }}</dd>
                    <dt>Total</dt><dd class="fw-bold">&#8369;{{ number_format($booking->total_amount, 2) }}</dd>
                </dl>
            </div>

            <div class="card-box">
                <h2 class="section-title">Guest authorization form</h2>
                <dl class="summary-list mb-0">
                    <dt>Full name</dt><dd>{{ $booking->authorized_name }}</dd>
                    <dt>Contact</dt><dd>{{ $booking->authorized_contact }}</dd>
                    <dt>Email</dt><dd>{{ $booking->authorized_email }}</dd>
                </dl>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card-box">
                <h2 class="section-title">Payment</h2>

                @if ($booking->payment_proof)
                    <a href="{{ route('files.proof', $booking) }}" target="_blank" class="btn btn-outline-primary mb-3">
                        <i class="bi bi-file-earmark-image"></i> View proof of payment
                    </a>
                <p class="mb-3">Payment reference no.: <strong>{{ $booking->payment_reference ?? '-' }}</strong></p>
                @else
                    <p class="text-muted">The guest has not uploaded a proof of payment yet.</p>
                @endif
                @if ($booking->booking_status === 'Paid')
                    <button class="btn btn-primary btn-post" data-url="{{ route('host.bookings.confirm', $booking) }}"
                            data-confirm="Did you receive the payment? A commission invoice will be created.">Confirm payment received</button>
                @elseif ($booking->booking_status === 'Confirmed')
                    <h3 class="section-title mt-3">Door passcode</h3>
                    @if ($booking->door_passcode)
                        <p class="pass-code">{{ $booking->door_passcode }}</p>
                    @else
                        <button class="btn btn-primary btn-post" data-url="{{ route('host.bookings.passcode', $booking) }}">Generate door passcode</button>
                    @endif
                    <button class="btn btn-outline-danger btn-post mt-2" data-url="{{ route('host.bookings.cancel', $booking) }}"
                            data-confirm="Cancel this confirmed booking? The dates will open again.">Cancel booking</button>
                @elseif ($booking->booking_status === 'Cancelled')
                    <p class="mb-0 text-muted">This booking was cancelled.</p>
                @endif
            </div>
        </div>
    </div>
@endsection