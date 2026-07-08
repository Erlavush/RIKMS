@props(['value' => 0, 'max' => 6, 'green' => false])

@php($percent = $max > 0 ? min(100, round(($value / $max) * 100)) : 0)
<div class="readiness">
    <div class="readiness-label"><span>READINESS</span><strong>{{ $value }}/{{ $max }}</strong></div>
    <div class="progress-track">
        <div class="progress-fill {{ $green ? 'green' : '' }}" style="width: {{ $percent }}%"></div>
    </div>
</div>
