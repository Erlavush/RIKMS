@props(['title', 'message'])

<div class="success-screen">
    <div class="success-check"><x-icon name="check" /></div>
    <h1>{{ $title }}</h1>
    <p>{!! $message !!}</p>
    {{ $slot }}
</div>
