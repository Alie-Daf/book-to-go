@extends('layouts.app')
@section('title', 'Admin dashboard')
@section('nav', 'admin.dashboard')
@section('topbar') @include('partials.topbar') @endsection

@push('vendor')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
@endpush

@section('content')
    <h1 class="page-title">Admin dashboard</h1>
    <p class="page-sub">Overview of users, listings, and bookings</p>

    <div class="stat-grid">
        <div class="stat-card"><div class="stat-label">Total Guests</div><div class="stat-value">{{ $totalGuests }}</div></div>
        <div class="stat-card"><div class="stat-label">Total Hosts</div><div class="stat-value">{{ $totalHosts }}</div></div>
        <div class="stat-card"><div class="stat-label">Total Units</div><div class="stat-value">{{ $totalUnits }}</div></div>
        <div class="stat-card"><div class="stat-label">Total Bookings</div><div class="stat-value">{{ $totalBookings }}</div></div>
    </div>

    <div class="row g-4 mt-1">
        <div class="col-lg-6"><div class="card-box"><h2 class="section-title">Booking overview</h2><div class="chart-box"><canvas id="overviewChart"></canvas></div></div></div>
        <div class="col-lg-6"><div class="card-box"><h2 class="section-title">Booking by status</h2><div class="chart-box"><canvas id="statusChart"></canvas></div></div></div>
    </div>

    {{-- ---------- Revenue ---------- --}}
    <div class="page-head mt-5">
        <h2 class="section-title mb-0">Revenue</h2>
        <form method="GET" action="{{ route('admin.dashboard') }}">
            <input type="month" name="month" value="{{ $month }}" class="form-control" onchange="this.form.submit()">
        </form>
    </div>

    <div class="stat-grid">
        <div class="stat-card"><div class="stat-label">Commission ({{ $month }})</div><div class="stat-value">&#8369;{{ number_format($commissionMonth) }}</div></div>
        <div class="stat-card"><div class="stat-label">Commission (all time)</div><div class="stat-value">&#8369;{{ number_format($commissionAll) }}</div></div>
        <div class="stat-card"><div class="stat-label">Unpaid</div><div class="stat-value">&#8369;{{ number_format($unpaidTotal) }}</div></div>
        <div class="stat-card"><div class="stat-label">Overdue</div><div class="stat-value text-danger">&#8369;{{ number_format($overdueTotal) }}</div></div>
        <div class="stat-card"><div class="stat-label">Active Featured</div><div class="stat-value">{{ $activeFeatured }}</div></div>
    </div>

    <div class="row g-4 mt-1">
        <div class="col-lg-7">
            <div class="card-box"><h2 class="section-title">Revenue per stream ({{ $month }})</h2><div class="chart-box"><canvas id="streamChart"></canvas></div></div>
        </div>
        <div class="col-lg-5">
            <div class="card-box">
                <h2 class="section-title">Hosts on hold</h2>
                @forelse ($onHold as $host)
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span>{{ $host->full_name }}</span>
                        <a href="{{ route('admin.invoices', ['status' => 'Overdue']) }}">View invoices</a>
                    </div>
                @empty
                    <p class="text-muted mb-0">No hosts on hold.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="card-box mt-4">
        <h2 class="section-title">Paid, but the host has not confirmed</h2>
        <p class="text-muted small">Guests paid more than {{ \App\Models\FeeSetting::get('stale_paid_hours', 48) }} hours ago. A host who hides a sale avoids the commission.</p>
        <table class="table mb-0">
            <thead><tr><th>Booking</th><th>Host</th><th>Guest</th><th>Paid</th><th></th></tr></thead>
            <tbody>
                @forelse ($staleBookings as $b)
                    <tr>
                        <td>{{ $b->booking_reference }}</td>
                        <td>{{ $b->unit->host->full_name }}</td>
                        <td>{{ $b->guest->full_name }}</td>
                        <td>{{ ($b->paid_at ?? $b->requested_at)->diffForHumans() }}</td>
                        <td><button class="btn btn-sm btn-outline-primary btn-post" data-url="{{ route('admin.bookings.remind', $b) }}">Remind host</button></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-3">Nothing waiting. All paid bookings are confirmed.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

@push('scripts')
<script>
    new Chart(document.getElementById('overviewChart'), {
        type: 'bar',
        data: { labels: @json($labels), datasets: [{ data: @json($values), backgroundColor: '#f0a05a', borderRadius: 8 }] },
        options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });

    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: ['Pending', 'Paid', 'Confirmed', 'Cancelled'],
            datasets: [{ data: [{{ $byStatus['Pending'] ?? 0 }}, {{ $byStatus['Paid'] ?? 0 }}, {{ $byStatus['Confirmed'] ?? 0 }}, {{ $byStatus['Cancelled'] ?? 0 }}],
                         backgroundColor: ['#ef7f72', '#e0d8c8', '#f0a05a', '#cfc8bb'] }]
        },
        options: { maintainAspectRatio: false, cutout: '55%', plugins: { legend: { position: 'bottom' } } }
    });

    new Chart(document.getElementById('streamChart'), {
        type: 'bar',
        data: { labels: @json($streamLabels), datasets: [{ data: @json($streamValues), backgroundColor: '#e0752f', borderRadius: 8 }] },
        options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });
</script>
@endpush