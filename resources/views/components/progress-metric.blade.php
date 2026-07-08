@props(['label', 'value' => 0, 'color' => 'green'])

<div class="metric-row">
    <div><span>{{ $label }}</span><strong>{{ $value }}%</strong></div>
    <div class="progress-track">
        <div class="progress-fill {{ $color }}" style="width: {{ $value }}%"></div>
    </div>
</div>
