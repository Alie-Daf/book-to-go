@extends('layouts.app')
@section('title', 'Users')
@section('nav', 'admin.users')
@section('topbar') @include('partials.topbar') @endsection

@section('content')
    <div class="page-head">
        <h1 class="page-title">Users</h1>
        <p class="page-sub">View, suspend, or remove accounts</p>

        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUser">+ Add user</button>
    </div>

    <div class="tabs-bar">
        @foreach ([''  => 'All', 'Guest' => 'Guests', 'Host' => 'Hosts'] as $key => $label)
            <a href="{{ route('admin.users', array_filter(['role' => $key])) }}" class="{{ (string) $role === (string) $key ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
    </div>

    <form class="live-search mb-3" action="{{ route('admin.users') }}">
        <input type="hidden" name="role" value="{{ $role }}">
        <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search name or email..." autocomplete="off">
    </form>

    <div id="liveResults">@include('admin.users-table')</div>

    <div class="modal fade" id="addUser" tabindex="-1">
        <div class="modal-dialog">
            <form class="modal-content ajax-form" action="{{ route('admin.users.store') }}">
                <div class="modal-header"><h2 class="modal-title fs-5">Add user</h2><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-6"><label class="form-label">First name</label><input name="first_name" class="form-control" required></div>
                        <div class="col-6"><label class="form-label">Last name</label><input name="last_name" class="form-control" required></div>
                    </div>
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control mb-3" required>
                    <label class="form-label">Password (at least 8 characters)</label>
                    <input type="password" name="password" class="form-control mb-3" required>
                    <label class="form-label">Role</label>
                    <select name="role" class="form-select"><option>Guest</option><option>Host</option><option>Admin</option></select>
                </div>
                <div class="modal-footer"><button class="btn btn-primary">Add user</button></div>
            </form>
        </div>
    </div>
@endsection