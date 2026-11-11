@extends('layouts.app')
@section('title', 'Image Packs')
@section('nav', 'host.packs')

@section('content')
    <h1 class="page-title">Image Packs</h1>

    <p class="text-muted">Every unit can have {{ $freeLimit }} photos for free. Buy a pack to show more. Extra photos appear after the admin verifies your payment.</p>

    <div class="table-card mb-4">
        <table class="table mb-0">
            <thead><tr><th>Pack</th><th>Images per unit</th><th>Price</th><th>Duration</th></tr></thead>
            <tbody>
                @foreach ($packs as $pack)
                    <tr><td>{{ $pack->pack_name }}</td><td>{{ $pack->max_images }}</td><td>&#8369;{{ number_format($pack->price, 2) }}</td><td>{{ $pack->duration_days }} days</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="card-box">
        <h2 class="section-title">Buy a pack</h2>
        <form class="ajax-form" action="{{ route('host.packs.buy') }}">
            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label">Unit</label>
                    <select name="unit_id" class="form-select" required>
                        @foreach ($units as $unit)
                            <option value="{{ $unit->unit_id }}">{{ $unit->title }} ({{ $unit->images->count() }} / {{ $unit->imageLimit() }} photos)</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Pack</label>
                    <select name="pack_id" class="form-select" required>
                        @foreach ($packs as $pack)
                            <option value="{{ $pack->pack_id }}">{{ $pack->pack_name }}: {{ $pack->max_images }} images, &#8369;{{ number_format($pack->price) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary w-100">Get invoice</button></div>
            </div>
        </form>
    </div>
@endsection