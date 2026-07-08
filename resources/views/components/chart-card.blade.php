@props(['title'])

<section class="card chart-card">
    <h2>{{ $title }}</h2>
    {{ $slot }}
</section>
