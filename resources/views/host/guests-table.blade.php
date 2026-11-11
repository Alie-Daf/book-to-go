<div class="table-card">
    <table class="table mb-0">
        <thead><tr><th>Guest Name</th><th>Unit</th><th>Check-in and Check-out</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
            @forelse ($bookings as $booking)
                <tr>
                    <td>{{ $booking->guest->full_name }}</td>
                    <td>{{ $booking->unit->title }}</td>
                    <td>{{ $booking->check_in_date->format('M j') }} - {{ $booking->check_out_date->format('M j, Y') }}</td>
                    <td><span class="status-pill status-{{ $booking->booking_status }}">{{ $booking->booking_status }}</span></td>
                    <td><a href="{{ route('host.bookings.show', $booking) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">No guests found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>