@extends('layouts.app')
@section('title', 'Reservation')
@section('nav', 'home')
@section('topbar') @include('reservation.steps', ['step' => 1]) @endsection

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css" rel="stylesheet">
@endpush
@push('vendor')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
@endpush

@section('content')
    <h1 class="page-title">Reservation details</h1>

    <div class="card-box d-flex p-0 overflow-hidden mb-4">
        <div class="photo summary-photo">
            <img src="{{ $unit->cover }}" alt="" onerror="this.remove()"><i class="bi bi-image"></i>
        </div>
        <div class="p-3">
            <h2 class="section-title mb-1">{{ $unit->title }}</h2>
            <div class="text-muted">{{ $unit->category }} &middot; {{ $unit->location }} &middot; &#8369;{{ number_format($unit->starting_rate) }} / night</div>
        </div>
    </div>

    <form id="reservationForm" method="POST" action="{{ route('reservation.save', $unit) }}"
          data-availability-url="{{ route('units.availability', $unit) }}" data-quote-url="{{ route('units.quote', $unit) }}"
          data-max-guests="{{ $unit->max_guests }}">
        @csrf

        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <div class="row mb-3 align-items-center">
            <label class="col-md-3 fw-semibold">Check-in date</label>
            <div class="col-md-9"><input type="text" id="check_in_date" name="check_in_date" class="form-control" placeholder="mm/dd/yyyy" value="{{ old('check_in_date') }}"></div>
        </div>
        <div class="row mb-3 align-items-center">
            <label class="col-md-3 fw-semibold">Check-out date</label>
            <div class="col-md-9"><input type="text" id="check_out_date" name="check_out_date" class="form-control" placeholder="mm/dd/yyyy" value="{{ old('check_out_date') }}"></div>
        </div>
        <div class="row mb-3 align-items-center">
            <label class="col-md-3 fw-semibold">Check-in time</label>
            <div class="col-md-9"><input type="text" class="form-control" value="{{ $unit->check_in_label }}" readonly></div>
        </div>
        <div class="row mb-4 align-items-center">
            <label class="col-md-3 fw-semibold">Check-out time</label>
            <div class="col-md-9"><input type="text" class="form-control" value="{{ $unit->check_out_label }}" readonly></div>
        </div>

        <label class="fw-semibold mb-1">Number of guests <span class="text-muted fw-normal">(max {{ $unit->max_guests }})</span></label>
        <input type="number" id="guest_count" name="guest_count" min="1" max="{{ $unit->max_guests }}" class="form-control mb-3" value="{{ old('guest_count') }}">

        <div id="quote" class="quote-box mb-3" hidden></div>

        <div class="form-check mb-4">
            <input class="form-check-input" type="checkbox" name="policy" id="policy" value="1">
            <label class="form-check-label" for="policy">I agree to the no-cancellation, no-refund policy.</label>
        </div>

        <button id="nextBtn" class="btn btn-primary w-100 py-2">Next</button>
    </form>
@endsection