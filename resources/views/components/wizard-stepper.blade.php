@props(['steps', 'current' => 1, 'flowLabel' => 'RESEARCH STUDY - SIMPLIFIED FLOW', 'total' => 6, 'locked' => false])

<section class="wizard-stepper card">
    <div class="stepper-top">
        <span>{{ $flowLabel }}</span>
        <x-badge tone="navy">{{ $total }} Steps</x-badge>
    </div>
    @if ($locked)
        <div class="locked-steps">Select a document type to see remaining steps</div>
    @endif
    <div class="stepper-track">
        @foreach ($steps as $number => $label)
            @php($state = $number < $current ? 'done' : ($number === $current ? 'current' : 'future'))
            <div class="stepper-item {{ $state }} {{ $locked && $number > 1 ? 'locked' : '' }}">
                <div class="step-circle">{{ $state === 'done' ? '✓' : $number }}</div>
                <div class="step-label">{{ $label }}</div>
                @if (!$loop->last)
                    <div class="step-line {{ $number < $current ? 'done' : '' }}"></div>
                @endif
            </div>
        @endforeach
    </div>
</section>
