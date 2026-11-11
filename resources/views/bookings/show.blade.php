@extends('layouts.app')
@section('title', 'Booking details')
@section('nav', 'bookings.index')

@section('content')
    @php
        $steps = ['Pending', 'Paid', 'Confirmed'];
        $reached = array_search($booking->booking_status, $steps);
        $reached = $reached === false ? -1 : $reached;
    @endphp

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h1 class="page-title mb-1">Booking details</h1>
            <div class="text-muted">Reference {{ $booking->booking_reference }}</div>
        </div>
        <span class="status-pill status-{{ $booking->booking_status }}">{{ $booking->booking_status }}</span>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card-box mb-4">
                <h2 class="section-title">Summary</h2>
                <dl class="summary-list mb-0">
                @if ($booking->unit->is_bookable)
                    <a href="{{ route('reservation.details', $booking->unit) }}" class="btn btn-primary px-4">Book now</a>
                @else
                    <span class="btn btn-secondary disabled">Temporarily unavailable</span>
                @endif
                    <dt>Check-in</dt><dd>{{ $booking->check_in_date->format('M j, Y') }}, {{ $booking->unit->check_in_label }}</dd>
                    <dt>Check-out</dt><dd>{{ $booking->check_out_date->format('M j, Y') }}, {{ $booking->unit->check_out_label }}</dd>
                    <dt>Guests</dt><dd>{{ $booking->guest_count }}</dd>
                    <dt>Total</dt><dd class="fw-bold">&#8369;{{ number_format($booking->total_amount, 2) }}</dd>
                </dl>
            </div>

            <div class="card-box">
                <h2 class="section-title">Payment</h2>
                <p>Pay the host directly, then upload your proof of payment.</p>
                <p class="payment-box"><i class="bi bi-wallet2"></i> {{ $booking->unit->host->payment_instructions ?? 'The host has not added payment details yet. Please send a message.' }}</p>

                <div class="status-track mb-3">
                    @foreach ($steps as $i => $label)
                        <span class="{{ $i <= $reached ? 'reached' : '' }}">{{ $label }}</span>
                    @endforeach
                </div>

                @if ($booking->booking_status === 'Pending')
                    <form class="ajax-form" action="{{ route('bookings.proof', $booking) }}" enctype="multipart/form-data">
                        <label class="form-label fw-semibold">Payment reference number (from your GCash or PayPal)</label>
                        <input name="payment_reference" class="form-control mb-3" maxlength="40" required>

                        <label class="form-label fw-semibold">Proof of payment (JPG or PNG, up to 2 MB)</label>
                        <div class="input-group">
                            <input type="file" name="proof" class="form-control" accept=".jpg,.jpeg,.png" required>
                            <button class="btn btn-primary">Upload</button>
                        </div>
                    </form>
                    <p class="small text-muted mt-3 mb-2">
                        Upload your proof within {{ \App\Models\FeeSetting::get('booking_hold_hours', 24) }} hours, or the dates are released.
                    </p>
                    <button class="btn btn-outline-danger btn-sm btn-post" data-url="{{ route('bookings.cancel', $booking) }}"
                            data-confirm="Cancel this booking request?">Cancel booking request</button>
                @endif
                <p class="text-muted small mt-3 mb-0">All bookings follow a no-cancellation, no-refund policy.</p>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card-box pass-card">
                <h2 class="section-title">Guest pass</h2>
                <div class="pass-code">{{ $booking->booking_reference }}</div>
                <dl class="summary-list mb-0">
                    <dt>Guest</dt><dd>{{ $booking->authorized_name }}</dd>
                    <dt>Contact</dt><dd>{{ $booking->authorized_contact }}</dd>
                    <dt>Unit</dt><dd>{{ $booking->unit->title }}</dd>
                    <dt>Stay</dt><dd>{{ $booking->check_in_date->format('M j') }} - {{ $booking->check_out_date->format('M j, Y') }}</dd>
                    <dt>Door passcode</dt>
                    <dd>
                        @if ($booking->booking_status === 'Confirmed' && $booking->door_passcode)
                            <strong class="passcode">{{ $booking->door_passcode }}</strong>
                        @else
                            <span class="text-muted">Shown once the host confirms</span>
                        @endif
                    </dd>
                </dl>
            </div>
        </div>
    </div>
@endsection