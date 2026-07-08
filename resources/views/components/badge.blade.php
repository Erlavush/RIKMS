@props(['tone' => 'blue'])

<span {{ $attributes->merge(['class' => 'badge badge-'.$tone]) }}>{{ $slot }}</span>
