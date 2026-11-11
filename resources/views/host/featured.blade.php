@extends('layouts.app')
@section('title', 'Featured Listing')
@section('nav', 'host.featured')

@section('content')
    <h1 class="page-title">Featured Listing</h1>

    <p class="text-muted">A Featured unit appears at the top of search results and in "Recommended for you".</p>

    <div class="card-box mb-4">
        <h2 class="section-title">Request a Featured Listing</h2>
        <form class="ajax-form" action="{{ route('host.featured.store') }}">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Unit (approved units only)</label>
                    <select name="unit_id" class="form-select" required>
                        @foreach ($units as $unit) <option value="{{ $unit->unit_id }}">{{ $unit->title }}</option> @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Start date</label>
                    <input type="date" name="start_date" class="form-control" min="{{ today()->toDateString() }}" value="{{ today()->toDateString() }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Duration</label>
                    <select name="days" class="form-select">
                        <option value="7">7 days (&#8369;{{ number_format($weekPrice) }})</option>
                        <option value="30">30 days (&#8369;{{ number_format($monthPrice) }})</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary w-100">Request</button></div>
            </div>
        </form>
    </div>

    <div class="table-card">
        <table class="table mb-0">
            <thead><tr><th>Unit</th><th>Dates</th><th>Invoice</th><th>Status</th></tr></thead>
            <tbody>
                @forelse ($listings as $listing)
                    <tr>
                        <td>{{ $listing->unit->title }}</td>
                        <td>{{ $listing->start_date->format('M j') }} - {{ $listing->end_date->format('M j, Y') }}</td>
                        <td><span class="status-pill {{ $listing->invoice->status_class }}">{{ $listing->invoice->status }}</span></td>
                        <td><span class="status-pill status-{{ $listing->featured_status }}">{{ $listing->featured_status }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">No requests yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection