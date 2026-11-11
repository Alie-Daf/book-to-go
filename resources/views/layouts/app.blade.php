<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') | Book To Go</title>
    <script>document.documentElement.dataset.theme = localStorage.getItem('theme') || 'light';</script>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    @stack('styles')
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>
<body>
<div class="app">
    @include('partials.sidebar')

    <div class="main" id="ajax-main">
        {{-- every page now has the top bar (search, bell, user) --}}
        @hasSection('topbar') @yield('topbar') @else @include('partials.topbar') @endif

        <main class="content" id="ajax-content">
            @if (session('warning'))
                <div class="alert alert-warning">{{ session('warning') }}</div>
            @endif

            @if (auth()->user()->role === 'Host' && auth()->user()->is_on_hold)
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill"></i> Account on hold: overdue invoice.
                    Settle it in <a href="{{ route('host.billing') }}" class="alert-link">Billing</a> to continue.
                </div>
            @endif

            @if (auth()->user()->role === 'Host' && ! auth()->user()->is_verified)
                <div class="alert alert-warning">
                    <i class="bi bi-shield-exclamation"></i> Host verification: <strong>{{ auth()->user()->verification?->status ?? 'Not submitted' }}</strong>.
                    You can add units after the admin verifies your documents. Check
                    <a href="{{ route('host.profile') }}" class="alert-link">Profile</a>.
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toasts"></div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('vendor')
<script src="{{ asset('js/app.js') }}"></script>
@stack('scripts')
</body>
</html>