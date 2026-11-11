@extends('layouts.app')
@section('title', 'Listings')
@section('nav', 'admin.listings')

@section('content')
    <h1 class="page-title">Listing approval</h1>

    <div class="table-card">
        <table class="table mb-0">
            <thead><tr><th>Photo</th><th>Unit</th><th>Host</th><th>Category</th><th>Location</th><th>Documents</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse ($units as $unit)
                    <tr>
                        <td><div class="photo table-photo"><img src="{{ $unit->cover }}" alt="" onerror="this.remove()"><i class="bi bi-image"></i></div></td>
                        <td>{{ $unit->title }}<small class="d-block text-muted">&#8369;{{ number_format($unit->starting_rate ?? 0) }} / night</small></td>
                        <td>
                            {{ $unit->host->full_name }}
                            @if ($unit->host->is_verified)
                                <small class="d-block verified"><i class="bi bi-patch-check-fill"></i> Verified</small>
                            @else
                                <small class="d-block text-muted">Not verified</small>
                            @endif
                        </td>
                        <td>{{ $unit->category }}</td>
                        <td>{{ $unit->location }}</td>
                        <td class="text-start">
                            @if ($unit->title_document)
                                <a href="{{ route('files.unit', [$unit, 'title']) }}" target="_blank">Title</a>
                            @else
                                <span class="text-danger">No title</span>
                            @endif
                            @if ($unit->permission_document)
                                <br><a href="{{ route('files.unit', [$unit, 'permission']) }}" target="_blank">Condo permission</a>
                            @elseif ($unit->category === 'Condo')
                                <br><span class="text-danger">No permission</span>
                            @endif
                        </td>
                        <td><span class="status-pill status-{{ $unit->listing_status }}">{{ $unit->listing_status }}</span></td>
                        <td>
                            @if ($unit->listing_status !== 'Approved')
                                <button class="btn btn-sm btn-primary btn-post" data-url="{{ route('admin.listings.decide', [$unit, 'approve']) }}">Approve</button>
                            @endif
                            @if ($unit->listing_status !== 'Inactive')
                                <button class="btn btn-sm btn-outline-danger btn-post" data-url="{{ route('admin.listings.decide', [$unit, 'reject']) }}"
                                        data-confirm="Take this listing offline?">Reject</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No listings yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection