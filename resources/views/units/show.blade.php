@extends('layouts.app')
@section('title', $unit->title)
@section('nav', 'home')
@section('topbar') @include('partials.topbar') @endsection

@section('content')
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="photo main-photo">
                <img id="mainPhoto" src="{{ $unit->cover }}" alt="{{ $unit->title }}" onerror="this.remove()">
                <i class="bi bi-image"></i>
            </div>
            <div class="row g-3 mt-0">
                @foreach ($unit->visible_images->slice(1) as $image)
                    <div class="col-4 mb-3">
                        <div class="photo thumb" data-src="{{ $image->image_url }}">
                            <img src="{{ $image->image_url }}" alt="" onerror="this.remove()">
                            <i class="bi bi-image"></i>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="col-lg-5">
            <h1 class="page-title mb-2">{{ $unit->title }}</h1>
            <p class="text-muted">{{ $unit->category }} &bull; {{ $unit->location }} &nbsp; <i class="bi bi-star-fill text-primary"></i> {{ $unit->rating_label }}</p>
            <p class="unit-price">&#8369;{{ number_format($unit->starting_rate) }} <span>/ night</span></p>

            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-primary btn-bookmark" data-url="{{ route('favorites.toggle', $unit) }}">
                    <i class="bi {{ $isBookmarked ? 'bi-heart-fill' : 'bi-heart' }}"></i>
                    <span class="label">{{ $isBookmarked ? 'Bookmarked' : 'Bookmark' }}</span>
                </button>
@if ($unit->is_bookable)
    <a href="{{ route('reservation.details', $unit) }}" class="btn btn-primary px-4">Book now</a>
@else
    <span class="btn btn-secondary disabled">Temporarily unavailable</span>
@endif
                <button type="button" class="btn btn-outline-secondary btn-post" title="Report this listing"
                        data-url="{{ route('units.report', $unit) }}" data-prompt="What is wrong with this listing?" data-field="reason">
                    <i class="bi bi-flag"></i>
                </button>
            </div>
        </div>
    </div>

    <ul class="nav nav-tabs page-tabs mt-5">
        @foreach (['overview' => 'Overview', 'amenities' => 'Amenities', 'reviews' => 'Review', 'location' => 'Location'] as $id => $label)
            <li class="nav-item">
                <button class="nav-link {{ $loop->first ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tab-{{ $id }}" type="button">{{ $label }}</button>
            </li>
        @endforeach
    </ul>

    <div class="tab-content pt-4">
        <div class="tab-pane fade show active" id="tab-overview">
            <p>{{ $unit->description }}</p>
            <div class="row g-3">
                <div class="col-md-4">
                    <h3 class="section-title">Rates by guests</h3>
                    @foreach ($unit->rates->sortBy('guest_count') as $rate)
                        <div>Up to {{ $rate->guest_count }} guests: <strong>&#8369;{{ number_format($rate->nightly_rate) }}</strong></div>
                    @endforeach
                </div>
                <div class="col-md-4">
                    <h3 class="section-title">Check-in / Check-out</h3>
                    <div>Check-in: {{ $unit->check_in_label }}</div>
                    <div>Check-out: {{ $unit->check_out_label }}</div>
                </div>
                <div class="col-md-4">
                    <h3 class="section-title">House rules</h3>
                    <div>{{ $unit->house_rules }}</div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="tab-amenities">
            @foreach ($unit->amenities as $amenity)
                <span class="amenity-pill">{{ $amenity->amenity_name }}</span>
            @endforeach
        </div>

        <div class="tab-pane fade" id="tab-reviews">
            @if ($reviewable)
                <form class="card-box mb-4 ajax-form" action="{{ route('units.review', $unit) }}">
                    <h3 class="section-title">How was your stay?</h3>
                    <input type="hidden" name="booking_id" value="{{ $reviewable->booking_id }}">
                    <div class="star-rating mb-3">
                        @for ($i = 5; $i >= 1; $i--)
                            <input type="radio" name="rating" id="star{{ $i }}" value="{{ $i }}" @checked($i === 5)>
                            <label for="star{{ $i }}"><i class="bi bi-star-fill"></i></label>
                        @endfor
                    </div>
                    <textarea name="comment" rows="3" class="form-control mb-3" placeholder="Share your feedback..." required></textarea>
                    <button class="btn btn-primary">Submit review</button>
                </form>
            @endif

            @forelse ($unit->reviews->sortByDesc('created_at') as $review)
                <div class="review-item">
                    <span class="avatar">{{ $review->guest->initials }}</span>
                    <div>
                        <strong>{{ $review->guest->full_name }}</strong>
                        <span class="text-primary ms-2">
                            @for ($i = 1; $i <= 5; $i++)<i class="bi {{ $i <= $review->rating ? 'bi-star-fill' : 'bi-star' }}"></i>@endfor
                        </span>
                        <div class="text-muted small">{{ $review->created_at->format('M j, Y') }}</div>
                        <p class="mb-0 mt-1">{{ $review->comment }}</p>
                    </div>
                </div>
            @empty
                <p class="text-muted">No reviews yet. Reviews open after a completed stay.</p>
            @endforelse
        </div>

        <div class="tab-pane fade" id="tab-location">
            <p><i class="bi bi-geo-alt"></i> {{ $unit->address_line }}, {{ $unit->city }}, {{ $unit->province }} {{ $unit->postal_code }}</p>
            <a class="btn btn-outline-primary btn-sm" target="_blank" href="https://www.google.com/maps/search/?api=1&query={{ urlencode($unit->address_line.' '.$unit->city) }}">Open in Maps</a>
        </div>
    </div>

    <div class="row g-4 mt-2">
        <div class="col-md-6">
            <div class="card-box h-100">
                <h3 class="section-title">Amenities</h3>
                <span class="text-muted">{{ $unit->amenities->pluck('amenity_name')->implode(' • ') }}</span>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card-box h-100 d-flex align-items-center gap-3">
                <span class="avatar avatar-lg">{{ $unit->host->initials }}</span>
                <div>
                    <h3 class="section-title mb-1">{{ $unit->host->full_name }}</h3>
                    @if ($unit->host->is_verified)
                        <div class="verified mb-2"><i class="bi bi-patch-check-fill"></i> Verified host</div>
                    @endif
                    <a href="{{ route('messages', ['with' => $unit->host->user_id]) }}" class="btn btn-outline-primary btn-sm">Message</a>
                </div>
            </div>
        </div>
    </div>
@endsection