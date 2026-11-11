@extends('layouts.auth')
@section('title', 'Log in')

@section('content')
    <div class="auth-logo"><span class="brand-logo"><i></i><i></i><i></i><i></i></span> BOOK TO GO</div>
    <h1 class="auth-title">Welcome back</h1>
    <p class="auth-sub">Book to Go</p>

    <form method="POST" action="{{ route('login') }}">
        @csrf
        <label class="form-label">Email Address</label>
        <input type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" required autofocus>
        @error('email') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror

        <label class="form-label mt-3">Password</label>
        <input type="password" name="password" class="form-control" required>

        <button class="btn btn-primary w-100 mt-4 py-2">Log in</button>
    </form>

    <p class="auth-links">
        Forgot Password?<br>&mdash; or &mdash;<br>
        Don't have an account? <a href="{{ route('register') }}">Register</a>
    </p>
@endsection