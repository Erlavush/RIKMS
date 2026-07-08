@props(['name', 'label', 'value' => ''])

<div class="metadata-field">
    <div class="metadata-field-header">
        <div><x-icon name="edit" /> <strong>{{ $label }}</strong></div>
        <div><x-badge tone="green">AI Detected</x-badge></div>
    </div>
    <textarea name="{{ $name }}" rows="{{ strlen($value) > 220 ? 5 : 3 }}">{{ old($name, $value) }}</textarea>
</div>
