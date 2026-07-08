<x-app-layout title="Upload Document">
    <x-page-header
        title="Upload Document"
        subtitle="Submit documents with structured data and automated metadata extraction"
        breadcrumb="Dashboard / Upload / New Document"
        badge="AI-Powered"
    />

    <x-wizard-stepper :steps="$steps" :current="1" flow-label="DEFAULT WIZARD FLOW" :total="6" locked />

    <form method="POST" action="{{ route('upload.select-type') }}" class="wizard-grid" data-doc-type-form>
        @csrf
        <section class="card form-card">
            <h2>Select Document Type</h2>
            <p class="muted">Choose the document type to configure the appropriate wizard flow for your submission.</p>

            <div class="flow-explain-grid">
                <div>
                    <h3>Research Study - 6-Step Simplified</h3>
                    <p>Upload -> AI Metadata -> SDG Tagging -> Access Control -> Review</p>
                </div>
                <div>
                    <h3>Reports - 9-Step Full Flow</h3>
                    <p>Details -> AI -> Performance -> PAP -> Financials -> Highlights -> SDG -> Review</p>
                </div>
            </div>

            <div class="doc-type-grid">
                <x-document-type-card value="research_study" badge="6-Step Simplified" title="Research Study" description="Peer-reviewed research papers and academic studies" icon="document" tone="blue" />
                <x-document-type-card value="terminal_report" badge="9-Step Full Flow" title="Terminal Report" description="End-of-project reports with performance data and outcomes" icon="archive" tone="purple" />
                <x-document-type-card value="project_accomplishment_report" badge="9-Step Full Flow" title="Project Accomplishment Report" description="PAP submissions for periodic monitoring and compliance" icon="check" tone="green" />
            </div>

            <div class="wizard-actions">
                <button class="btn-secondary" type="button" disabled>Previous</button>
                <button class="btn-primary" type="submit" disabled data-doc-type-continue>Continue -></button>
            </div>
        </section>

        <aside class="preview-stack">
            <x-research-preview-sidebar :step="1" :total="6" :steps="$steps" />
            <x-flow-checklist :steps="$steps" :current="1" title="DEFAULT WIZARD FLOW" />
        </aside>
    </form>
</x-app-layout>
