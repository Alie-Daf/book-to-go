@extends('layouts.app')
@section('title', 'Reviews')
@section('nav', 'host.reviews')
@section('topbar') @include('partials.topbar') @endsection

@section('content')
    <h1 class="page-title">Reviews</h1>
    <p class="page-sub">What guests say about your units</p>

    <div class="card-box mb-4">
        <div class="row align-items-center">
            <div class="col-md-4">
                <div class="stat-value">{{ number_format($average, 1) }}</div>
                <div class="text-primary fs-5">
                    @for ($i = 1; $i <= 5; $i++) <i class="bi bi-star{{ $i <= round($average) ? '-fill' : '' }}"></i> @endfor
                </div>
                <div class="text-muted">{{ $count }} reviews</div>
            </div>
            <div class="col-md-8">
                @foreach ([5, 4, 3, 2, 1] as $star)
                    @php $n = $spread[$star] ?? 0; @endphp
                    <div class="rating-row">
                        <span>{{ $star }}&#9733;</span>
                        <div class="rating-bar"><div style="width: {{ $count ? $n / $count * 100 : 0 }}%"></div></div>
                        <span>{{ $n }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <form class="live-search row g-2 mb-4" action="{{ route('host.reviews') }}">
        <div class="col-md-5"><input type="search" name="q" class="form-control" placeholder="Search..." autocomplete="off"></div>
        <div class="col-md-3">
            <select name="unit_id" class="form-select">
                <option value="">All units</option>
                @foreach ($units as $u) <option value="{{ $u->unit_id }}">{{ $u->title }}</option> @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select name="rating" class="form-select">
                <option value="">All ratings</option>
                @foreach ([5, 4, 3, 2, 1] as $r) <option value="{{ $r }}">{{ $r }} stars</option> @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select name="sort" class="form-select"><option value="newest">Newest</option><option value="oldest">Oldest</option></select>
        </div>
    </form>

    <div id="liveResults">@include('host.reviews-list')</div>
@endsection