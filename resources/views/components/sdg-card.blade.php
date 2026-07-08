@props(['sdg', 'selected' => false])

<label class="sdg-card {{ $selected ? 'selected' : '' }}" style="--sdg-color: {{ $sdg->color }}">
    <input type="checkbox" name="selected_sdgs[]" value="{{ $sdg->number }}" @checked($selected) data-sdg-input>
    <span>SDG</span>
    <strong>{{ $sdg->number }}</strong>
    <em>{{ $sdg->short_name }}</em>
    <b class="sdg-check">✓</b>
</label>
