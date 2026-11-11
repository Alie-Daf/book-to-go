@extends('layouts.app')
@php $editing = $unit->exists; @endphp
@section('title', $editing ? 'Edit unit' : 'Add new unit')
@section('nav', 'host.units.index')

@section('content')
    <h1 class="page-title">{{ $editing ? 'Edit unit' : 'Add new unit' }}</h1>

    <form class="ajax-form" action="{{ $editing ? route('host.units.update', $unit) : route('host.units.store') }}" enctype="multipart/form-data">

        <div class="card-box mb-4">
            <h2 class="section-title">Unit details</h2>
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Title</label>
                    <input name="title" class="form-control" value="{{ $unit->title }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select">
                        @foreach ($categories as $category)
                            <option @selected($unit->category === $category)>{{ $category }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" rows="3" class="form-control">{{ $unit->description }}</textarea>
                </div>
                <div class="col-md-6"><label class="form-label">Address / building</label><input name="address_line" class="form-control" value="{{ $unit->address_line }}" required></div>
                <div class="col-md-3"><label class="form-label">City</label><input name="city" class="form-control" value="{{ $unit->city }}" required></div>
                <div class="col-md-3"><label class="form-label">Province</label><input name="province" class="form-control" value="{{ $unit->province }}" required></div>
                <div class="col-md-3"><label class="form-label">Postal code</label><input name="postal_code" class="form-control" value="{{ $unit->postal_code }}"></div>
                <div class="col-md-3"><label class="form-label">Max guests</label><input type="number" min="1" name="max_guests" class="form-control" value="{{ $unit->max_guests }}" required></div>
                <div class="col-md-3"><label class="form-label">Check-in time</label><input type="time" name="check_in_time" class="form-control" value="{{ substr($unit->check_in_time, 0, 5) }}" required></div>
                <div class="col-md-3"><label class="form-label">Check-out time</label><input type="time" name="check_out_time" class="form-control" value="{{ substr($unit->check_out_time, 0, 5) }}" required></div>
                <div class="col-12">
                    <label class="form-label">House rules</label>
                    <textarea name="house_rules" rows="2" class="form-control" required>{{ $unit->house_rules }}</textarea>
                </div>
            </div>
        </div>

        <div class="card-box mb-4">
            <h2 class="section-title">Amenities</h2>
            @foreach ($amenities as $amenity)
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" name="amenities[]" id="am{{ $amenity->amenity_id }}"
                           value="{{ $amenity->amenity_id }}" @checked(in_array($amenity->amenity_id, $selected))>
                    <label class="form-check-label" for="am{{ $amenity->amenity_id }}">{{ $amenity->amenity_name }}</label>
                </div>
            @endforeach
        </div>

        <div class="card-box mb-4">
            <h2 class="section-title">Rates by number of guests (pax)</h2>
            <p class="text-muted small">Example: up to 2 guests = 1,800 per night. Up to 4 guests = 2,400 per night.</p>

            <div id="rates">
                @foreach ($rates as $i => $rate)
                    <div class="row g-2 mb-2 rate-row">
                        <div class="col-5"><input type="number" min="1" name="rates[{{ $i }}][guest_count]" class="form-control" placeholder="Up to how many guests" value="{{ $rate->guest_count }}" required></div>
                        <div class="col-5"><input type="number" min="1" step="0.01" name="rates[{{ $i }}][nightly_rate]" class="form-control" placeholder="Price per night" value="{{ $rate->nightly_rate }}" required></div>
                        <div class="col-2"><button type="button" class="btn btn-outline-danger remove-rate"><i class="bi bi-trash"></i></button></div>
                    </div>
                @endforeach
            </div>
            <button type="button" id="addRate" class="btn btn-sm btn-outline-primary">+ Add rate</button>

            <template id="rateTemplate">
                <div class="row g-2 mb-2 rate-row">
                    <div class="col-5"><input type="number" min="1" name="rates[__i__][guest_count]" class="form-control" placeholder="Up to how many guests" required></div>
                    <div class="col-5"><input type="number" min="1" step="0.01" name="rates[__i__][nightly_rate]" class="form-control" placeholder="Price per night" required></div>
                    <div class="col-2"><button type="button" class="btn btn-outline-danger remove-rate"><i class="bi bi-trash"></i></button></div>
                </div>
            </template>
        </div>

        <div class="card-box mb-4">
            <h2 class="section-title">Proof that you may rent out this unit</h2>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Title of this lot / unit / house</label>
                    <input type="file" name="title_document" class="form-control" accept=".jpg,.jpeg,.png,.pdf" @required(! $unit->title_document)>
                    @if ($unit->title_document)
                        <small class="text-success">Uploaded. Choose a file only to replace it (the unit goes back to admin review).</small>
                    @endif
                </div>
                <div class="col-md-6">
                    <label class="form-label">Condo / HOA permission to rent out (required for condos)</label>
                    <input type="file" name="permission_document" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                    @if ($unit->permission_document) <small class="text-success">Uploaded.</small> @endif
                </div>
            </div>
        </div>
        
        <div class="card-box mb-4">
            <h2 class="section-title">Photos</h2>
            <p class="text-muted small mb-2">
                @if ($editing)
                    You use {{ $unit->images->count() }} of {{ $unit->imageLimit() }} photos.
                @else
                    Free limit: {{ \App\Models\FeeSetting::get('free_image_limit', 7) }} photos.
                @endif
                Need more? <a href="{{ route('host.packs') }}">Buy an image pack</a>.
            </p>
            @if ($editing && $unit->images->count())
                <div class="d-flex flex-wrap gap-2 mb-3">
                    @foreach ($unit->images as $image)
                        <div class="position-relative">
                            <div class="photo thumb-sm"><img src="{{ $image->image_url }}" alt="" onerror="this.remove()"><i class="bi bi-image"></i></div>
                            <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 btn-post"
                                    data-url="{{ route('host.photos.delete', $image) }}" data-confirm="Remove this photo?"><i class="bi bi-x"></i></button>
                        </div>
                    @endforeach
                </div>
            @endif
            <input type="file" name="photos[]" class="form-control" accept="image/*" multiple>
            <small class="text-muted">JPG or PNG, up to 4 MB each.</small>
        </div>

        <button class="btn btn-primary px-4">Save unit</button>
        <a href="{{ route('host.units.index') }}" class="btn btn-outline-secondary">Cancel</a>
    </form>
@endsection