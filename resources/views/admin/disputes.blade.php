@extends('layouts.app')
@section('title', 'Disputes')
@section('nav', 'admin.disputes')

@section('content')
    <h1 class="page-title">Reports and disputes</h1>


    <div class="table-card">
        <table class="table mb-0">
            <thead><tr><th>Date</th><th>Reported by</th><th>About</th><th>Reason</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
                @forelse ($reports as $r)
                    <tr>
                        <td>{{ $r->created_at->format('M j, Y') }}</td>
                        <td>{{ $r->reporter->full_name }}<small class="d-block text-muted">{{ $r->reporter->role }}</small></td>
                        <td class="text-start">
                            @if ($r->review)
                                Review by {{ $r->review->guest->full_name }} on <strong>{{ $r->unit?->title }}</strong>
                                <small class="d-block text-muted">"{{ \Illuminate\Support\Str::limit($r->review->comment, 60) }}"</small>
                            @else
                                Listing: <strong>{{ $r->unit?->title }}</strong>
                                <small class="d-block"><a href="{{ route('admin.listings') }}">Go to Listings</a></small>
                            @endif
                        </td>
                        <td class="text-start">{{ $r->reason }}</td>
                        <td><span class="status-pill status-{{ $r->status }}">{{ $r->status }}</span></td>
                        <td>
                            @if ($r->status === 'Open')
                                <button class="btn btn-sm btn-primary btn-post" data-url="{{ route('admin.disputes.resolve', $r) }}"
                                        data-prompt="Resolution notes" data-field="notes">Resolve</button>
                            @else
                                <small class="text-muted">{{ $r->resolution_notes }}</small>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No reports. All clear!</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection