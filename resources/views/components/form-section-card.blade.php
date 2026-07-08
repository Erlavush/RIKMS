@props(['title', 'subtitle' => null, 'icon' => null, 'badge' => null])

<section class="card form-card">
    <div class="form-card-head">
        <div>
            @if ($icon)
                <span class="section-icon"><x-icon :name="$icon" /></span>
            @endif
            <h2>{{ $title }}</h2>
            @if ($badge)
                <x-badge tone="navy">{{ $badge }}</x-badge>
            @endif
            @if ($subtitle)
                <p>{{ $subtitle }}</p>
            @endif
        </div>
    </div>
    {{ $slot }}
</section>
