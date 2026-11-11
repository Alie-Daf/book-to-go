@extends('layouts.app')
@section('title', 'My Bookings')
@section('nav', 'bookings.index')

@section('content')
    <h1 class="page-title">My Bookings</h1>
    <p class="page-sub">Track every reservation</p>

    <div class="tabs-bar">
        @foreach (['all' => 'All', 'upcoming' => 'Upcoming', 'past' => 'Past', 'cancelled' => 'Cancelled'] as $key => $label)
            <a href="{{ route('bookings.index', ['tab' => $key]) }}" class="{{ $tab === $key ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
    </div>

    @forelse ($bookings as $booking)
        <article class="unit-card">
            <div class="photo">
                <img src="{{ $booking->unit->cover }}" alt="" onerror="this.remove()"><i class="bi bi-image"></i>
            </div>
            <div class="unit-body align-items-center">
                <div>
                    <h3 class="unit-title"><a href="{{ route('bookings.show', $booking) }}" class="stretched-link">{{ $booking->unit->title }}</a></h3>
                    <div class="unit-meta">{{ $booking->check_in_date->format('M j') }} - {{ $booking->check_out_date->format('M j, Y') }} &middot; {{ $booking->unit->location }}</div>
                    <small class="text-muted">{{ $booking->booking_reference }}</small>
                </div>
                <span class="status-pill status-{{ $booking->booking_status }}">{{ $booking->booking_status }}</span>
            </div>
        </article>
    @empty
        <div class="card-box text-center text-muted py-5">No bookings here yet.</div>
    @endforelse
@endsection