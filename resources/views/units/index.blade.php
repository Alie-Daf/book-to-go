@extends('layouts.app')
@section('title', 'Browse units')
@section('nav', 'home')
@section('topbar') @include('partials.topbar') @endsection

@section('content')
    <h1 class="page-title">Browse units</h1>
    <p class="page-sub">Filter by category, pax, and price</p>
    
    <div class="row g-4">
        <div class="col-lg-4 col-xl-3">
            <form id="filters" class="card-box" action="{{ route('units.index') }}">
                <h2 class="section-title">Filters</h2>

                <label class="form-label">Location</label>
                <input type="text" name="location" class="form-control mb-3" value="{{ request('location') }}">

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label">Guests</label>
                        <input type="number" name="guests" min="1" class="form-control" value="{{ request('guests') }}">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Max price</label>
                        <input type="number" name="max_price" min="0" step="100" class="form-control" value="{{ request('max_price') }}">
                    </div>
                </div>

                <div class="fw-semibold mb-1">Unit category</div>
                @foreach ($categories as $category)
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="categories[]" value="{{ $category }}" id="cat{{ $category }}"
                               @checked(in_array($category, (array) request('categories')))>
                        <label class="form-check-label" for="cat{{ $category }}">{{ $category }}</label>
                    </div>
                @endforeach

                <div class="fw-semibold mt-3 mb-1">Amenities</div>
                @foreach ($amenities as $amenity)
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="amenities[]" value="{{ $amenity->amenity_name }}" id="am{{ $amenity->amenity_id }}"
                               @checked(in_array($amenity->amenity_name, (array) request('amenities')))>
                        <label class="form-check-label" for="am{{ $amenity->amenity_id }}">{{ $amenity->amenity_name }}</label>
                    </div>
                @endforeach

                <button type="button" id="clearFilters" class="btn btn-link px-0 mt-3">Clear filters</button>
            </form>
        </div>

        <div class="col-lg-8 col-xl-9" id="results">
            @include('units.results')
        </div>
    </div>
@endsection