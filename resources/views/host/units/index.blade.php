@extends('layouts.app')
@section('title', 'Units')
@section('nav', 'host.units.index')
@section('topbar') @include('partials.topbar') @endsection

@section('content')
    <div class="page-head">
        <h1 class="page-title">Units</h1>
        <p class="page-sub">Manage your listings</p>
        
        <a href="{{ route('host.units.create') }}" class="btn btn-primary">+ Add new unit</a>
    </div>

    <div class="table-card">
        <table class="table mb-0">
            <thead><tr><th>Photo</th><th>Unit Name</th><th>Category</th><th>Price/night</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse ($units as $unit)
                    <tr>
                        <td><div class="photo table-photo"><img src="{{ $unit->cover }}" alt="" onerror="this.remove()"><i class="bi bi-image"></i></div></td>
                        <td>{{ $unit->title }}</td>
                        <td>{{ $unit->category }}</td>
                        <td>&#8369;{{ number_format($unit->starting_rate ?? 0) }}</td>
                        <td><span class="status-pill status-{{ $unit->listing_status }}">{{ $unit->listing_status }}</span></td>
                        <td>
                            <a href="{{ route('host.units.edit', $unit) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            @if ($unit->listing_status !== 'Pending' && $unit->approved_at)
                                <button class="btn btn-sm btn-outline-secondary btn-post" data-url="{{ route('host.units.toggle', $unit) }}">
                                    {{ $unit->listing_status === 'Approved' ? 'Deactivate' : 'Activate' }}
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No units yet. Click "Add new unit".</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection