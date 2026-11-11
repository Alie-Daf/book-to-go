@extends('layouts.app')
@section('title', 'Favorites')
@section('nav', 'favorites')

@section('content')
    <h1 class="page-title">Favorites</h1>

    @forelse ($units as $unit)
        @include('partials.unit-card', ['removeOnUnbookmark' => true])
    @empty
        <div class="card-box text-center text-muted py-5">No favorites yet. Tap the heart on a unit to save it here.</div>
    @endforelse
@endsection