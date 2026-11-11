@extends('layouts.app')
@section('title', 'Invoice Verification')
@section('nav', 'admin.invoices')

@section('content')
    <h1 class="page-title">Invoice Verification</h1>

    <form class="live-search row g-2 mb-4" action="{{ route('admin.invoices') }}">
        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="">All statuses</option>
                @foreach (['Unpaid', 'For Verification', 'Paid', 'Overdue', 'Cancelled'] as $s) <option>{{ $s }}</option> @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <select name="type" class="form-select">
                <option value="">All types</option>
                @foreach (['Commission', 'Image Pack', 'Unit Slot', 'Featured'] as $t) <option>{{ $t }}</option> @endforeach
            </select>
        </div>
        <div class="col-md-6"><input type="search" name="q" class="form-control" placeholder="Search host name..." autocomplete="off"></div>
    </form>

    <div id="liveResults">@include('admin.invoices-table')</div>
@endsection