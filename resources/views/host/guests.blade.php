@extends('layouts.app')
@section('title', 'Guest Information')
@section('nav', 'host.guests')
@section('topbar') @include('partials.topbar') @endsection

@section('content')
    <h1 class="page-title">Guest Information</h1>
    <p class="page-sub">Guests who booked your units</p>

    <form class="live-search mb-3" action="{{ route('host.guests') }}">
        <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search guest name..." autocomplete="off">
    </form>

    <div id="liveResults">@include('host.guests-table')</div>
@endsection