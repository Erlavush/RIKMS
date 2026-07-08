@props(['value', 'badge', 'title', 'description', 'tone' => 'blue', 'icon' => 'document'])

<label class="doc-type-card doc-type-{{ $tone }}" data-doc-type-card>
    <input type="radio" name="document_type" value="{{ $value }}" class="sr-only" data-doc-type-input>
    <div class="doc-type-icon"><x-icon :name="$icon" /></div>
    <x-badge :tone="$tone">{{ $badge }}</x-badge>
    <h3>{{ $title }}</h3>
    <p>{{ $description }}</p>
</label>
