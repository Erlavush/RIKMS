@props(['value', 'label', 'description', 'icon' => 'shield', 'selected' => false])

<label class="access-option {{ $selected ? 'selected' : '' }}" data-access-option>
    <input type="radio" name="access_mode" value="{{ $value }}" @checked($selected) data-access-input>
    <span class="access-radio"></span>
    <span class="access-icon"><x-icon :name="$icon" /></span>
    <span>
        <strong>{{ $label }}</strong>
        <small>{{ $description }}</small>
    </span>
    <x-badge tone="navy" class="selected-badge">Selected</x-badge>
</label>
