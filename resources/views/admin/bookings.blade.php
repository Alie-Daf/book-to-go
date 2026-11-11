@extends('layouts.app')
@section('title', 'Bookings')
@section('nav', 'admin.bookings')
@section('topbar') @include('partials.topbar') @endsection

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css" rel="stylesheet">
@endpush
@push('vendor')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
@endpush

@section('content')
    <h1 class="page-title">Bookings management</h1>
    <p class="page-sub">Monitor bookings and payment confirmations</p>

    <form class="live-search row g-3 mb-4" action="{{ route('admin.bookings') }}">
        <div class="col-md-6">
            <select name="status" class="form-select">
                <option value="">All statuses</option>
                @foreach (['Pending', 'Paid', 'Confirmed', 'Cancelled'] as $s) <option>{{ $s }}</option> @endforeach
            </select>
        </div>
        <div class="col-md-6"><input type="text" name="range" class="form-control date-range" placeholder="Date range (check-in)" autocomplete="off"></div>
    </form>

    <div id="liveResults">@include('admin.bookings-table')</div>
@endsection