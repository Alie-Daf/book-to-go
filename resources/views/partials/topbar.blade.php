@php
    $me = auth()->user();
    $searchUrl = match ($me->role) { 'Guest' => route('units.index'), 'Host' => route('host.guests'), default => route('admin.users') };
    $profileUrl = match ($me->role) { 'Guest' => route('profile'), 'Host' => route('host.profile'), default => route('admin.dashboard') };
@endphp
<header class="topbar">
    <form id="topSearchForm" class="search-form" method="GET" action="{{ $searchUrl }}">
        <i class="bi bi-search"></i>
        <input type="search" id="topSearch" name="q" value="{{ request('q') }}" placeholder="Search..." autocomplete="off">
    </form>

    <a href="{{ route('notifications') }}" class="icon-circle" title="Notifications">
        <i class="bi bi-bell"></i>
        @if ($me->unreadNotifications()->count()) <span class="dot"></span> @endif
    </a>

    <div class="dropdown">
        <button class="user-chip" data-bs-toggle="dropdown">
            <span class="avatar">{{ $me->initials }}</span>
            <span class="user-meta"><strong>{{ $me->first_name }}</strong><small>{{ $me->role === 'Admin' ? 'Administrator' : $me->role }}</small></span>
            <i class="bi bi-chevron-down"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="{{ $profileUrl }}">My profile</a></li>
            <li><form method="POST" action="{{ route('logout') }}">@csrf<button class="dropdown-item">Logout</button></form></li>
        </ul>
    </div>
</header>