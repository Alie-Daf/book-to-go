@extends('layouts.app')
@section('title', 'Home')
@section('nav', 'home')

@section('content')
    <section class="hero">
        <div>
            <h1>Find your perfect<br>stay, worry-free.</h1>
            <p>Browse verified units, check live availability, and book with a few taps.</p>
            <a href="{{ route('units.index') }}" class="btn btn-primary btn-lg">Browse units</a>
            <div class="hero-proof">
                <span class="proof-avatars"><i></i><i></i></span>
                <small>Verified hosts<br>Secure check-in</small>
            </div>
        </div>
        <div class="hero-visual">
            <div class="hero-card hero-card-back"><span>&#9733;&#9733;&#9733;&#9733;&#9733; &middot; {{ $unitCount }} units</span></div>
            <div class="hero-card hero-card-front">
                <span>&#9733;&#9733;&#9733;&#9733;&#9733; &middot; Verified listings</span>
                <strong>Find your stay,<br>comfortably.</strong>
            </div>
        </div>
    </section>

    <div class="d-flex justify-content-between align-items-end mt-4 mb-3">
        <h2 class="section-title mb-0">Popular categories</h2>
        <a href="{{ route('units.index') }}" class="text-muted small">View all</a>
    </div>

    @php
        // category => [icon, circle colour, icon colour]
        $icons = ['Room' => ['door-open', '#fbe0c5', '#e0752f'], 'Studio' => ['lamp', '#e9e2d6', '#8a8176'], 'Apartment' => ['building', '#f9d6d2', '#d9594c'],
                  'Condo' => ['buildings', '#fbe5c0', '#d6a02a'], 'House' => ['house-door', '#fbe0c5', '#e0752f']];
    @endphp
    <div class="category-grid">
        @foreach ($categories as $name => $photo)
            <a href="{{ route('units.index', ['categories' => [$name]]) }}" class="category-card">
                <span class="cat-icon" style="background: {{ $icons[$name][1] }}; color: {{ $icons[$name][2] }}"><i class="bi bi-{{ $icons[$name][0] }}"></i></span>
                <span class="cat-text"><strong>{{ strtoupper($name) }}</strong><small>Browse units</small></span>
                <i class="bi bi-arrow-up-right text-muted"></i>
            </a>
        @endforeach
    </div>

    @if ($featured->isNotEmpty())
        <h2 class="section-title mt-5">Recommended for you</h2>
        @foreach ($featured as $unit)
            @include('partials.unit-card')
        @endforeach
    @endif
@endsection