@extends('layouts.app')
@section('title', 'Featured Approval')
@section('nav', 'admin.featured')

@section('content')
    <h1 class="page-title">Featured Approval</h1>


    <p class="text-muted">You can approve a request after the host's invoice is Paid.</p>

    <div class="table-card">
        <table class="table mb-0">
            <thead><tr><th>Unit</th><th>Host</th><th>Dates</th><th>Invoice</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse ($listings as $listing)
                    <tr>
                        <td>{{ $listing->unit->title }}</td>
                        <td>{{ $listing->unit->host->full_name }}</td>
                        <td>{{ $listing->start_date->format('M j') }} - {{ $listing->end_date->format('M j, Y') }}</td>
                        <td><span class="status-pill {{ $listing->invoice->status_class }}">{{ $listing->invoice->status }}</span></td>
                        <td>
                            <span class="status-pill status-{{ $listing->featured_status }}">{{ $listing->featured_status }}</span>
                            @if ($listing->featured_status === 'Pending' && $listing->approved_by)
                                <small class="d-block text-muted">Approved, starts {{ $listing->start_date->format('M j') }}</small>
                            @endif
                        </td>
                        <td>
                            @if ($listing->featured_status === 'Pending' && ! $listing->approved_by && $listing->invoice->status === 'Paid')
                                <button class="btn btn-sm btn-primary btn-post" data-url="{{ route('admin.featured.decide', [$listing, 'approve']) }}">Approve</button>
                                <button class="btn btn-sm btn-outline-danger btn-post" data-url="{{ route('admin.featured.decide', [$listing, 'reject']) }}"
                                        data-confirm="Reject this request?">Reject</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No featured requests yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection