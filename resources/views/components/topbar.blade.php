@php($user = auth()->user())

<header class="topbar">
    <div class="topbar-search">
        <x-icon name="search" />
        <input type="search" placeholder="Search research records..." aria-label="Search research records">
    </div>

    <div class="topbar-actions">
        <button class="icon-button notification-button" type="button" aria-label="Notifications">
            <x-icon name="bell" />
            <span class="unread-dot"></span>
        </button>
        <div class="topbar-profile">
            <div class="avatar avatar-small">A</div>
            <div class="profile-text">
                <strong>Agency Admin</strong>
                <span>{{ $user?->name ?? 'test' }}</span>
            </div>
            <x-icon name="chevron-down" class="muted-icon" />
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="icon-button" type="submit" aria-label="Logout">
                <x-icon name="logout" />
            </button>
        </form>
    </div>
</header>
