@props(['fields', 'selected' => []])

<section class="public-selector">
    <div class="selector-head">
        <div>
            <h3>Select Metadata for Public Display</h3>
            <p>Only selected fields will be visible in the public repository.</p>
        </div>
        <div class="selector-actions">
            <button type="button" data-select-all-public>Select All</button>
            <button type="button" data-clear-public>Clear All</button>
        </div>
    </div>
    <div class="selector-list">
        @foreach ($fields as $field => $label)
            <label>
                <input type="checkbox" name="public_fields[]" value="{{ $field }}" @checked(in_array($field, old('public_fields', $selected), true)) data-public-field>
                <span>{{ $label }}</span>
                <x-badge tone="blue">Public</x-badge>
            </label>
        @endforeach
    </div>
</section>
