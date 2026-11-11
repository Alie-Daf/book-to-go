@php
    // [label, route name]
    $menu = [
        'Guest' => [['Home', 'home'], ['Bookings', 'bookings.index'], ['Favorites', 'favorites'], ['Messages', 'messages'], ['Guest Profile', 'profile']],
        'Host' => [['Dashboard', 'host.dashboard'], ['Unit Management', 'host.units.index'], ['Guest', 'host.guests'], ['Messages', 'messages'],
                   ['Availability', 'host.availability'], ['Reviews', 'host.reviews'], ['Billing', 'host.billing'], ['Image Packs', 'host.packs'],
                   ['Featured', 'host.featured'], ['Profile', 'host.profile']],
        'Admin' => [['Dashboard', 'admin.dashboard'], ['Users', 'admin.users'], ['Verifications', 'admin.verifications'], ['Listings', 'admin.listings'],
                    ['Bookings', 'admin.bookings'], ['Invoices', 'admin.invoices'], ['Featured', 'admin.featured'], ['Messages', 'messages'],
                    ['Reports', 'admin.reports'], ['Disputes', 'admin.disputes'], ['Audit Log', 'admin.audit'], ['Fee Settings', 'admin.fees']],
    ][auth()->user()->role];

    $menu[] = ['Notifications', 'notifications'];                              // every role has notifications
    $unread = auth()->user()->unreadNotifications()->count();
    $active = trim($__env->yieldContent('nav'));   // each page sets its own @section('nav', '...')
@endphp

<aside class="sidebar">
    <a href="/" class="brand"><span class="brand-logo"><i></i><i></i><i></i><i></i></span> BOOK TO GO</a>

    <div class="side-label">Menu</div>
    <nav>
        @foreach ($menu as [$label, $route])
            <a href="{{ route($route) }}"
                class="nav-item-link ajax-nav {{ $active === $route ? 'active' : '' }}"
                data-nav="{{ $route }}">
                {{ $label }}
                @if ($route === 'notifications' && $unread) <span class="bell-count">{{ $unread }}</span> @endif
            </a>
        @endforeach
    </nav>

    <div class="side-bottom">
        <div class="side-label">Account</div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="nav-item-link">Logout</button>
        </form>
        <div class="theme-toggle">
            <button type="button" data-theme-set="light" title="Light"><i class="bi bi-brightness-high"></i></button>
            <button type="button" data-theme-set="dark" title="Dark"><i class="bi bi-moon"></i></button>
        </div>
    </div>
</aside>