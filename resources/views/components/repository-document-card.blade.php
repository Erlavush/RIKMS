@props(['document'])

@php
    $metadata = $document->metadata;
    $authors = $metadata?->authors ? implode(', ', $metadata->authors) : 'test';
    $statusTone = ['published' => 'green', 'pending' => 'blue', 'draft' => 'yellow', 'archived' => 'gray'][$document->status] ?? 'gray';
@endphp

<article class="repo-card repo-border-{{ $document->id % 3 }}">
    <div class="repo-card-top">
        <x-badge tone="blue">{{ $document->documentTypeLabel() }}</x-badge>
        <x-badge tone="gray">{{ $document->year }}</x-badge>
        <x-badge :tone="$statusTone">{{ $document->statusLabel() }}</x-badge>
    </div>
    <h2>{{ $document->title }}</h2>
    <p class="authors">{{ \Illuminate\Support\Str::limit($authors, 100) }}</p>
    <p class="abstract">{{ \Illuminate\Support\Str::limit($metadata?->abstract ?? $document->description, 150) }}</p>

    <div class="access-box {{ $document->access_mode === 'public_download' ? 'public' : 'request' }}">
        <x-icon :name="$document->access_mode === 'public_download' ? 'shield' : 'archive'" />
        <strong>{{ $document->accessModeLabel() }}</strong>
        @if ($document->access_mode !== 'public_download')
            <span>Managed by agency</span>
        @endif
    </div>

    <div class="repo-meta-row">
        <span>{{ $document->category ?? 'Uncategorized' }}</span>
        @if ($document->is_ai_tagged)
            <x-badge tone="purple">AI tagged</x-badge>
        @endif
    </div>
    <x-progress-metric label="Completion" :value="$document->completion_score" color="green" />
    <x-progress-metric label="Digital Library" :value="$document->digital_library_score" color="navy" />

    <div class="tag-row">
        @foreach ($document->sdgTags as $sdg)
            <span class="sdg-pill" style="--sdg-color: {{ $sdg->color }}">SDG {{ $sdg->number }}</span>
        @endforeach
    </div>

    <div class="repo-card-actions">
        @if ($document->access_mode === 'request_access')
            <form method="POST" action="{{ route('documents.request-access', $document) }}">
                @csrf
                <button class="request-pill" type="submit">Request Access</button>
            </form>
        @else
            <x-badge tone="yellow">Draft</x-badge>
        @endif
        <button type="button" class="kebab">...</button>
    </div>
</article>
