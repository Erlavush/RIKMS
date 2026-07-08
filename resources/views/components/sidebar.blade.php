@php
    $items = [
        ['label' => 'Dashboard', 'route' => 'dashboard', 'match' => 'dashboard', 'icon' => 'grid'],
        ['label' => 'Research Repository', 'route' => 'repository', 'match' => 'repository', 'icon' => 'document'],
        ['label' => 'Upload Research', 'route' => 'upload.new', 'match' => 'upload.*', 'icon' => 'upload'],
        ['label' => 'Access Requests', 'route' => 'access-requests.index', 'match' => 'access-requests.*', 'icon' => 'shield'],
        ['label' => 'Archive', 'route' => 'archive', 'match' => 'archive', 'icon' => 'archive'],
        ['label' => 'Analytics', 'route' => 'analytics', 'match' => 'analytics', 'icon' => 'chart'],
        ['label' => 'Notifications', 'route' => 'notifications', 'match' => 'notifications', 'icon' => 'bell'],
        ['label' => 'Agency Profile', 'route' => 'agency-profile', 'match' => 'agency-profile', 'icon' => 'building'],
        ['label' => 'Settings', 'route' => 'settings', 'match' => 'settings', 'icon' => 'settings'],
    ];
    $user = auth()->user();
@endphp

<aside class="sidebar">
    <div class="brand-row">
        <div class="brand-mark">R</div>
        <div class="brand-name">RIKMS</div>
        <x-icon name="chevrons-left" class="brand-collapse" />
    </div>

    <nav class="sidebar-nav">
        @foreach ($items as $item)
            <a href="{{ route($item['route']) }}" class="sidebar-link {{ request()->routeIs($item['match']) ? 'active' : '' }}">
                <x-icon :name="$item['icon']" />
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>

    <div class="sidebar-user">
        <div class="avatar avatar-soft">A</div>
        <div>
            <div class="tiny-muted">Logged in as</div>
            <div class="user-name">{{ $user?->name ?? 'test' }}</div>
            <div class="tiny-muted">Agency Administrator</div>
        </div>
    </div>
</aside>
