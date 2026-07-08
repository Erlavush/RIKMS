<x-app-layout :title="$document->title">
    <x-page-header :title="$document->title" :subtitle="$document->documentTypeLabel().' · '.$document->statusLabel()" />
    <div class="wizard-grid">
        <section class="card form-card">
            <h2>Repository Preview</h2>
            <p class="muted">{{ $document->metadata?->abstract ?? $document->description }}</p>
            <div class="tag-row">
                @foreach ($document->sdgTags as $sdg)
                    <span class="sdg-pill" style="--sdg-color: {{ $sdg->color }}">SDG {{ $sdg->number }} - {{ $sdg->short_name }}</span>
                @endforeach
            </div>
            <div class="summary-grid">
                <div><span>Access</span><strong>{{ $document->accessModeLabel() }}</strong></div>
                <div><span>Completion</span><strong>{{ $document->completion_score }}%</strong></div>
                <div><span>Digital Library</span><strong>{{ $document->digital_library_score }}%</strong></div>
            </div>
        </section>
        <x-research-preview-sidebar :document="$document" :step="6" :total="$document->usesReportFlow() ? 9 : 6" />
    </div>
</x-app-layout>
