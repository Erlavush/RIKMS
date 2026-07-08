@props(['value', 'label', 'icon' => 'file', 'tone' => 'blue'])

<div class="stat-card">
    <div class="stat-icon stat-{{ $tone }}"><x-icon :name="$icon" /></div>
    <div>
        <div class="stat-value">{{ $value }}</div>
        <div class="stat-label">{{ $label }}</div>
    </div>
</div>
