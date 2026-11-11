@extends('layouts.app')
@section('title', 'Reports')
@section('nav', 'admin.reports')
@section('topbar') @include('partials.topbar') @endsection

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css" rel="stylesheet">
@endpush
@push('vendor')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
@endpush

@section('content')
    <h1 class="page-title">Reports</h1>
    <p class="page-sub">Generate booking and revenue reports</p>

    <form method="GET" action="{{ route('admin.reports') }}" class="row g-3 mb-4">
        <div class="col-md-4"><input type="text" name="range" value="{{ request('range') }}" class="form-control date-range" placeholder="Date range (default: last 6 months)" autocomplete="off"></div>
        <div class="col-md-4">
            <select name="type" class="form-select">
                <option value="month" @selected($type === 'month')>Revenue by month</option>
                <option value="unit" @selected($type === 'unit')>Revenue by unit</option>
            </select>
        </div>
        <div class="col-md-4"><button class="btn btn-primary w-100">Generate</button></div>
    </form>

    <div class="stat-grid mb-4">
        <div class="stat-card"><div class="stat-label">Total bookings</div><div class="stat-value">{{ $totalBookings }}</div></div>
        <div class="stat-card"><div class="stat-label">Gross bookings value</div><div class="stat-value">&#8369;{{ number_format($revenue) }}</div></div>
        <div class="stat-card"><div class="stat-label">Occupancy</div><div class="stat-value">{{ $occupancy }}%</div></div>
        <div class="stat-card"><div class="stat-label">Platform income (fees paid)</div><div class="stat-value">&#8369;{{ number_format($platformIncome) }}</div></div>
    </div>

    <div class="card-box">
        <h2 class="section-title">Bookings value overview</h2>
        <div class="chart-box"><canvas id="revenueChart"></canvas></div>
    </div>
    <p class="text-muted small mt-2">Only confirmed bookings are counted.</p>
@endsection

@push('scripts')
<script>
    new Chart(document.getElementById('revenueChart'), {
        type: '{{ $type === 'unit' ? 'bar' : 'line' }}',
        data: {
            labels: @json($chart->keys()),
            datasets: [{ data: @json($chart->values()), borderColor: '#ee9a55', backgroundColor: '#f6c9a0', borderRadius: 8, tension: .3 }]
        },
        options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });
</script>
@endpush