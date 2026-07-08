@props(['title', 'subtitle' => null, 'breadcrumb' => null, 'badge' => null])

<div class="page-header">
    <div>
        @if ($breadcrumb)
            <div class="breadcrumb">{{ $breadcrumb }}</div>
        @endif
        <h1>{{ $title }}</h1>
        @if ($subtitle)
            <p>{{ $subtitle }}</p>
        @endif
    </div>
    @if ($badge)
        <div class="header-badge">{{ $badge }}</div>
    @endif
</div>
