@extends('layouts.app')
@section('title', 'Availability')
@section('nav', 'host.availability')
@section('topbar') @include('partials.topbar') @endsection

@section('content')
    <h1 class="page-title">Availability Calendar</h1>
    <p class="page-sub">Set open dates. Booked dates are blocked automatically.</p>

    @if (! $unit)
        <div class="card-box text-center text-muted py-5">Add a unit first to manage its calendar.</div>
    @else
        <form method="GET" class="mb-3">
            <input type="hidden" name="month" value="{{ $month->format('Y-m') }}">
            <select name="unit" class="form-select w-auto" onchange="this.form.submit()">
                @foreach ($units as $u)
                    <option value="{{ $u->unit_id }}" @selected($u->unit_id == $unit->unit_id)>{{ $u->title }}</option>
                @endforeach
            </select>
        </form>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <a class="btn btn-outline-primary" href="{{ route('host.availability', ['unit' => $unit->unit_id, 'month' => $month->copy()->subMonth()->format('Y-m')]) }}">&lsaquo;</a>
            <h2 class="section-title mb-0">{{ $month->format('F Y') }}</h2>
            <a class="btn btn-outline-primary" href="{{ route('host.availability', ['unit' => $unit->unit_id, 'month' => $month->copy()->addMonth()->format('Y-m')]) }}">&rsaquo;</a>
        </div>

        <div class="calendar">
            @foreach (['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'] as $name)
                <div class="cal-head">{{ $name }}</div>
            @endforeach

            @for ($i = 0; $i < $month->dayOfWeek; $i++) <div></div> @endfor

            @for ($day = 1; $day <= $month->daysInMonth; $day++)
                @php
                    $date = $month->copy()->day($day)->toDateString();
                    $status = $ranges->first(fn ($r) => substr($r->start_date, 0, 10) <= $date && substr($r->end_date, 0, 10) >= $date)?->availability_status ?? 'Available';
                @endphp
                <div class="cal-day {{ $status }}" title="{{ $status }}">{{ $day }}</div>
            @endfor
        </div>

        <div class="cal-legend mt-3">
            <span><i class="legend-box Available"></i> Available</span>
            <span><i class="legend-box Booked"></i> Booked</span>
            <span><i class="legend-box Blocked"></i> Blocked</span>
        </div>

        <div class="row g-4 mt-1">
            <div class="col-lg-6">
                <div class="card-box">
                    <h2 class="section-title">Block dates</h2>
                    <form class="ajax-form" action="{{ route('host.availability.block', $unit) }}">
                        <div class="row g-2">
                            <div class="col"><label class="form-label small">From</label><input type="date" name="start_date" class="form-control" min="{{ today()->toDateString() }}" required></div>
                            <div class="col"><label class="form-label small">To</label><input type="date" name="end_date" class="form-control" min="{{ today()->toDateString() }}" required></div>
                        </div>
                        <input name="notes" class="form-control mt-2" placeholder="Note (optional)">
                        <button class="btn btn-primary mt-3">Block dates</button>
                    </form>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card-box">
                    <h2 class="section-title">Blocked dates</h2>
                    @forelse ($blocked as $range)
                        <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                            <span>{{ \Carbon\Carbon::parse($range->start_date)->format('M j, Y') }} - {{ \Carbon\Carbon::parse($range->end_date)->format('M j, Y') }}</span>
                            <button class="btn btn-sm btn-outline-danger btn-post" data-url="{{ route('host.availability.unblock', $range) }}">Open again</button>
                        </div>
                    @empty
                        <p class="text-muted mb-0">No blocked dates.</p>
                    @endforelse
                </div>
            </div>
        </div>
    @endif
@endsection