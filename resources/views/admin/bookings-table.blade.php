<div class="table-card">
    <table class="table mb-0">
        <thead><tr><th>ID</th><th>Guests</th><th>Unit</th><th>Check-in</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
            @forelse ($bookings as $b)
                <tr>
                    <td>{{ $b->booking_reference }}</td>
                    <td>{{ $b->guest->full_name }}</td>
                    <td>{{ $b->unit->title }}</td>
                    <td>{{ $b->check_in_date->format('M j, Y') }}</td>
                    <td><span class="status-pill status-{{ $b->booking_status }}">{{ $b->booking_status }}</span></td>
                    <td>
                        @if ($b->payment_proof)
                            <a href="{{ route('files.proof', $b) }}" target="_blank" class="btn btn-sm btn-outline-primary">Proof</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No bookings found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>