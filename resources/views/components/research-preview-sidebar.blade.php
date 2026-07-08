@props(['document' => null, 'step' => 1, 'total' => 6, 'steps' => []])

@php
    $metadata = $document?->metadata;
    $title = $document?->title ?: $metadata?->title ?: ($document ? 'Untitled Research' : 'Untitled Document');
    $typeLabel = $document?->documentTypeLabel() ?? 'Choose document type';
    $publicFields = $document?->publicFields?->where('is_public', true)->pluck('field_name')->all() ?? [];
@endphp

<aside class="preview-stack">
    <section class="preview-card">
        <div class="preview-head">
            <strong>{{ $document?->isResearchStudy() || !$document ? 'Research Preview' : 'Report Preview' }}</strong>
            <span>Step {{ $step }}/{{ $total }}</span>
        </div>
        <div class="preview-body">
            <x-badge tone="blue">{{ $typeLabel }}</x-badge>
            <h3>{{ $title }}</h3>
            @if ($metadata)
                <p>{{ \Illuminate\Support\Str::limit($metadata->abstract, 95) }}</p>
                <div class="public-preview">
                    <strong>PUBLIC VIEW</strong>
                    @foreach (['title' => 'TITLE', 'abstract' => 'ABSTRACT', 'methodology' => 'METHODOLOGY', 'results_and_discussion' => 'RESULTS AND DISCUSSION'] as $field => $label)
                        @if (in_array($field, $publicFields, true))
                            <span>{{ $label }}</span>
                            <p>{{ \Illuminate\Support\Str::limit(is_array($metadata->{$field}) ? implode(', ', $metadata->{$field}) : $metadata->{$field}, 88) }}</p>
                        @endif
                    @endforeach
                    @if (empty($publicFields))
                        <p>No public metadata selected yet.</p>
                    @endif
                </div>
            @endif
            @if ($document?->sdgTags?->isNotEmpty())
                <div class="tag-row">
                    @foreach ($document->sdgTags as $sdg)
                        <span class="sdg-pill" style="--sdg-color: {{ $sdg->color }}">SDG {{ $sdg->number }}</span>
                    @endforeach
                </div>
            @endif
            <div class="access-preview"><x-icon name="shield" /> {{ $document?->accessModeLabel() ?? 'Public Download' }}</div>
            <x-readiness-bar :value="$step" :max="$total" :green="$step === $total" />
        </div>
    </section>

    @if ($steps)
        <x-flow-checklist :steps="$steps" :current="$step" :title="$total.'-STEP '.($total === 9 ? 'REPORT FLOW' : 'RESEARCH FLOW')" />
    @endif
</aside>
