@props(['steps', 'current' => 1, 'title' => 'FLOW'])

<div class="flow-card">
    <h3>{{ $title }}</h3>
    <ol>
        @foreach ($steps as $number => $label)
            <li class="{{ $number < $current ? 'done' : ($number === $current ? 'current' : '') }}">
                <span>{{ $number < $current ? '✓' : $number }}</span>
                <b>{{ $label }}</b>
            </li>
        @endforeach
    </ol>
</div>
