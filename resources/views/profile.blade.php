@extends('layouts.app')
@section('title', 'Guest Profile')
@section('nav', 'profile')

@section('content')
    <div class="text-center mb-4">
        <span class="avatar avatar-xl">{{ $user->initials }}</span>
        <h1 class="page-title mt-3 mb-0">{{ $user->full_name }}</h1>
        <p class="text-muted">{{ $user->email }}</p>
    </div>

    <div class="row g-4 justify-content-center">
        <div class="col-md-5"><div class="stat-card"><div class="stat-label">Bookings</div><div class="stat-value">{{ $bookingCount }}</div></div></div>
        <div class="col-md-5"><div class="stat-card"><div class="stat-label">Reviews</div><div class="stat-value">{{ $reviewCount }}</div></div></div>
    </div>

    <div class="row justify-content-center mt-4">
        <div class="col-md-10">
            <div class="card-box">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2 class="section-title mb-0">Valid ID (age verification)</h2>
                    <span class="status-pill">{{ $idDocument?->verification_status ?? 'Not uploaded' }}</span>
                </div>
                <p class="text-muted">A valid ID is required before you can book a unit.</p>

                <form class="ajax-form" action="{{ route('profile.id') }}" enctype="multipart/form-data">
                    <div class="input-group">
                        <input type="file" name="valid_id" class="form-control" accept=".jpg,.jpeg,.png,.pdf" required>
                        <button class="btn btn-primary">{{ $idDocument ? 'Replace ID' : 'Upload ID' }}</button>
                    </div>
                </form>
            </div>
            @include('partials.privacy-card')
        </div>
    </div>
@endsection