@extends('layouts.app')
@section('title', 'Guest authorization')
@section('nav', 'home')
@section('topbar') @include('reservation.steps', ['step' => 2]) @endsection

@section('content')
    <h1 class="page-title">Guest authorization</h1>

    <h2 class="section-title mb-4">Primary guest</h2>

    <form method="POST" action="{{ route('reservation.store', $unit) }}">
        @csrf

        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <div class="row mb-3 align-items-center">
            <label class="col-md-3 fw-semibold">Full name</label>
            <div class="col-md-9"><input type="text" name="authorized_name" class="form-control" value="{{ old('authorized_name', auth()->user()->full_name) }}" required></div>
        </div>
        <div class="row mb-3 align-items-center">
            <label class="col-md-3 fw-semibold">Contact number</label>
            <div class="col-md-9"><input type="tel" name="authorized_contact" class="form-control" value="{{ old('authorized_contact') }}" required></div>
        </div>
        <div class="row mb-3 align-items-center">
            <label class="col-md-3 fw-semibold">Email Address</label>
            <div class="col-md-9"><input type="email" name="authorized_email" class="form-control" value="{{ old('authorized_email', auth()->user()->email) }}" required></div>
        </div>
        <div class="row mb-5 align-items-center">
            <label class="col-md-3 fw-semibold">Number of guests</label>
            <div class="col-md-9"><input type="text" class="form-control" value="{{ $draft['guest_count'] }}" readonly></div>
        </div>

        <div class="text-center">
            <div class="form-check d-inline-block text-start mb-3">
                <input class="form-check-input" type="checkbox" name="agree" id="agree" value="1">
                <label class="form-check-label" for="agree">I agree to the terms and conditions and authorize this information.</label>
            </div>
            <div><button class="btn btn-primary px-5">Submit Authorization</button></div>
        </div>
    </form>
@endsection