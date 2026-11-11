@extends('layouts.app')
@section('title', 'Notifications')
@section('nav', 'notifications')

@section('content')
    <div class="page-head">
        <h1 class="page-title">Notifications</h1>

        <button class="btn btn-outline-primary btn-post" data-url="{{ route('notifications.readall') }}">Mark all as read</button>
    </div>

    @forelse ($notices as $notice)
        <a href="{{ route('notifications.open', $notice->id) }}" class="card-box d-block mb-2 text-decoration-none">
            <div class="{{ $notice->read_at ? 'text-muted' : 'fw-bold' }}">{{ $notice->data['message'] }}</div>
            <small class="text-muted">{{ $notice->created_at->diffForHumans() }}</small>
        </a>
    @empty
        <div class="card-box text-center text-muted py-5">No notifications yet.</div>
    @endforelse
@endsection