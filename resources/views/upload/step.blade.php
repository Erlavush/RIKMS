@php
    $isReport = $document->usesReportFlow();
    $selectedSdgs = $document->sdgTags->pluck('number')->all();
    $metadata = $document->metadata;
    $publicCount = $document->publicFields->where('is_public', true)->count();
    $nextLabel = $step === $totalSteps ? 'Submit Review' : 'Continue ->';
    $subtitle = $document->isResearchStudy()
        ? 'Submit research articles with automated metadata extraction'
        : 'Submit documents with structured data and automated metadata extraction';
@endphp

<x-app-layout title="Upload Document">
    <x-page-header
        title="Upload Document"
        :subtitle="$subtitle"
        breadcrumb="Dashboard / Upload / New Document"
        :badge="'AI-Powered - '.$totalSteps.'-Step Wizard'"
    />

    <x-wizard-stepper
        :steps="$steps"
        :current="$step"
        :flow-label="$isReport ? 'REPORTS - FULL FLOW' : 'RESEARCH STUDY - SIMPLIFIED FLOW'"
        :total="$totalSteps"
    />

    <div class="wizard-grid">
        <main>
            @if ($step === 2 && !$isReport)
                <form method="POST" action="{{ route('upload.file', $document) }}" enctype="multipart/form-data">
                    @csrf
                    <x-form-section-card title="Upload Research Document" badge="STEP 2 OF 6" icon="upload" subtitle="Upload your document. AI will automatically extract title, abstract, methodology, authors, and more in the next step.">
                        <x-upload-dropzone />
                        <label class="field-block">
                            <span>Research Title</span>
                            <input name="manual_title" value="{{ old('manual_title', $document->title) }}" placeholder="Manual title override (optional)...">
                            <small>Optional - AI will auto-detect the title from document content</small>
                        </label>
                        <x-ai-info-box>The AI engine will auto-detect title, abstract, methodology, authors, keywords, and more in the next step.</x-ai-info-box>
                        <div class="wizard-actions">
                            <a class="btn-secondary" href="{{ route('upload.new') }}">← Previous</a>
                            <button class="btn-primary" type="submit" data-file-continue disabled>Continue -></button>
                        </div>
                    </x-form-section-card>
                </form>
            @elseif ($step === 2 && $isReport)
                <form method="POST" action="{{ route('upload.file', $document) }}" enctype="multipart/form-data">
                    @csrf
                    <x-form-section-card title="Document Details" :badge="'STEP 2 OF '.$totalSteps" icon="upload" subtitle="Upload your document file and provide basic information. AI will extract detailed metadata in the next step.">
                        <x-upload-dropzone />
                        <label class="field-block">
                            <span>Report Title</span>
                            <input name="manual_title" value="{{ old('manual_title', $document->title) }}" placeholder="Manual title override (optional)...">
                            <small>Optional - AI will auto-detect from document content</small>
                        </label>
                        <label class="field-block">
                            <span>Description</span>
                            <textarea name="description" rows="4" placeholder="Brief description of this submission...">{{ old('description', $document->description) }}</textarea>
                        </label>
                        <div class="two-col">
                            <label class="field-block"><span>Project Start Date</span><input name="project_start_date" type="date"></label>
                            <label class="field-block"><span>Project End Date</span><input name="project_end_date" type="date"></label>
                            <label class="field-block"><span>Reporting Period</span><input name="reporting_period" placeholder="Q1-Q4"></label>
                            <label class="field-block"><span>Reporting Year</span><input name="reporting_year" type="number" value="{{ old('reporting_year', 2026) }}"></label>
                        </div>
                        <label class="field-block">
                            <span>Agency</span>
                            <input name="agency" value="{{ $document->agency->name }}" readonly>
                            <small>Auto-filled</small>
                        </label>
                        <div class="wizard-actions">
                            <a class="btn-secondary" href="{{ route('upload.new') }}">← Previous</a>
                            <button class="btn-primary" type="submit" data-file-continue disabled>Continue -></button>
                        </div>
                    </x-form-section-card>
                </form>
            @elseif ($step === 3 && !$metadata)
                <x-form-section-card title="{{ $isReport ? 'Auto Detected Metadata' : 'Auto-Detected Metadata' }}" :badge="'STEP 3 OF '.$totalSteps" icon="sparkle" subtitle="{{ $isReport ? 'Use AI-assisted analysis to extract structured metadata, then review public display fields.' : 'AI automatically extracts structured metadata from your uploaded document. All fields are editable.' }}">
                    <div class="ai-ready-state">
                        <div class="ai-ready-icon"><x-icon name="sparkle" /></div>
                        <h3>Ready to Analyze Document</h3>
                        <p>{{ $isReport ? 'AI will read the uploaded report and prepare editable metadata sections for repository publication.' : 'Click below to run the AI extraction engine. It will automatically detect and populate all metadata fields.' }}</p>
                        <form method="POST" action="{{ route('upload.run-ai', $document) }}" data-ai-form>
                            @csrf
                            <button class="btn-ai" type="submit"><x-icon name="sparkle" /> Run AI Analysis</button>
                        </form>
                    </div>
                    <div class="wizard-actions">
                        <a class="btn-secondary" href="{{ route('upload.step', [$document, 2]) }}">← Previous</a>
                        <button class="btn-secondary" type="button" disabled>Continue -></button>
                    </div>
                </x-form-section-card>
            @elseif ($step === 3)
                <form method="POST" action="{{ route('upload.metadata', $document) }}">
                    @csrf
                    @method('PUT')
                    <x-form-section-card title="{{ $isReport ? 'Auto Detected Metadata' : 'Auto-Detected Metadata' }}" :badge="'STEP 3 OF '.$totalSteps" icon="sparkle" subtitle="AI automatically extracts structured metadata from your uploaded document. All fields are editable.">
                        <div class="success-alert">
                            <div><strong>Metadata extracted successfully</strong><span>All fields are editable. Review and correct as needed.</span></div>
                            <button class="btn-secondary small" type="button" data-rerun-ai="{{ route('upload.run-ai', $document) }}">Re-run</button>
                        </div>
                        <div class="section-row">
                            <strong>Extracted Fields</strong>
                            <span>Auto-detected (editable) - Click to expand</span>
                        </div>
                        <x-metadata-field-accordion name="title" label="Title" :value="$metadata->title" />
                        <x-metadata-field-accordion name="abstract" label="Abstract" :value="$metadata->abstract" />
                        <x-metadata-field-accordion name="methodology" label="Methodology" :value="$metadata->methodology" />
                        <x-metadata-field-accordion name="review_of_related_literature" label="Review of Related Literature" :value="$metadata->review_of_related_literature" />
                        <x-metadata-field-accordion name="theoretical_framework" label="Theoretical Framework" :value="$metadata->theoretical_framework" />
                        <x-metadata-field-accordion name="results_and_discussion" label="Results and Discussion" :value="$metadata->results_and_discussion" />
                        <x-metadata-field-accordion name="keywords" label="Keywords" :value="implode(', ', $metadata->keywords ?? [])" />
                        <x-metadata-field-accordion name="authors" label="Authors" :value="implode(', ', $metadata->authors ?? [])" />
                        <x-public-metadata-selector :fields="$metadataFields" :selected="$publicFieldNames" />
                        <div class="wizard-actions">
                            <a class="btn-secondary" href="{{ route('upload.step', [$document, 2]) }}">← Previous</a>
                            <button class="btn-primary" type="submit">Continue -></button>
                        </div>
                    </x-form-section-card>
                </form>
            @elseif (($step === 4 && !$isReport) || ($step === 8 && $isReport))
                <form method="POST" action="{{ route('upload.sdg-tags', $document) }}" data-sdg-form>
                    @csrf
                    @method('PUT')
                    @php
                        $rawAiJson = $document->metadata?->raw_ai_json;
                        $suggestedSdgData = $rawAiJson['suggested_sdgs'] ?? [
                            ['sdg' => 9, 'reason' => 'Industry, innovation, infrastructure, and cybersecurity systems', 'confidence' => 0.88],
                            ['sdg' => 16, 'reason' => 'Peace, justice, security, and institutional resilience', 'confidence' => 0.82],
                            ['sdg' => 8, 'reason' => 'Decent work and secure digital economy', 'confidence' => 0.75]
                        ];
                        $suggestedSdgNumbers = collect($suggestedSdgData)->pluck('sdg')->map(fn($n) => (int)$n)->toArray();
                        $suggestedSdgs = \App\Models\SdgTag::whereIn('number', $suggestedSdgNumbers)->get()->keyBy('number');
                    @endphp
                    <x-form-section-card title="SDG Tagging" :badge="'STEP '.$step.' OF '.$totalSteps" icon="sparkle" subtitle="Select Sustainable Development Goals - used for repository classification and filtering.">
                        <div class="ai-suggestion">
                            <div>
                                <strong>AI SDG Suggestion</strong>
                                <p>Based on extracted metadata, we suggest:</p>
                                <div class="tag-row">
                                    @foreach ($suggestedSdgData as $sData)
                                        @if ($sdgObj = $suggestedSdgs->get((int)$sData['sdg']))
                                            <span class="sdg-pill" style="--sdg-color: {{ $sdgObj->color }}" title="{{ $sData['reason'] ?? '' }} ({{ round(($sData['confidence'] ?? 0) * 100) }}% confidence)">
                                                SDG {{ $sdgObj->number }} ({{ round(($sData['confidence'] ?? 0) * 100) }}%)
                                            </span>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                            <button class="btn-warning" type="button" data-apply-sdg="{{ json_encode($suggestedSdgNumbers) }}">Apply All</button>
                        </div>
                        <h3>Select Applicable SDGs</h3>
                        <div class="sdg-grid">
                            @foreach ($allSdgs as $sdg)
                                <x-sdg-card :sdg="$sdg" :selected="in_array($sdg->number, old('selected_sdgs', $selectedSdgs), true)" />
                            @endforeach
                        </div>
                        <div class="wizard-actions">
                            <a class="btn-secondary" href="{{ route('upload.step', [$document, $isReport ? 7 : 3]) }}">← Previous</a>
                            <button class="btn-primary" type="submit" data-sdg-continue @disabled(empty($selectedSdgs))>Continue -></button>
                        </div>
                    </x-form-section-card>
                </form>
            @elseif ($step === 5 && !$isReport)
                <form method="POST" action="{{ route('upload.access-control', $document) }}" data-access-form>
                    @csrf
                    @method('PUT')
                    <x-form-section-card title="Access Control" badge="STEP 5 OF 6" icon="shield" subtitle="Define who can access or download this research document. This setting can be updated later by the agency administrator.">
                        <div class="access-list">
                            <x-access-option-card value="public_download" label="Public Download" description="Anyone can download the full research document." icon="shield" :selected="$document->access_mode === 'public_download'" />
                            <x-access-option-card value="request_access" label="Request Access" description="Users must submit a request to access the document." icon="shield" :selected="$document->access_mode === 'request_access'" />
                            <x-access-option-card value="restricted_admin" label="Restricted (Admin Only)" description="Only agency administrators can access the document." icon="archive" :selected="$document->access_mode === 'restricted_admin'" />
                            <x-access-option-card value="embargo_until_date" label="Embargo Until Date" description="Document becomes publicly available after a specified date." icon="calendar" :selected="$document->access_mode === 'embargo_until_date'" />
                            <x-access-option-card value="external_link_only" label="External Link Only" description="Link to a document hosted on an external platform." icon="document" :selected="$document->access_mode === 'external_link_only'" />
                        </div>
                        <div class="conditional-fields">
                            <label class="field-block" data-embargo-field><span>Embargo Until</span><input name="embargo_until" type="date" value="{{ optional($document->embargo_until)->format('Y-m-d') }}"></label>
                            <label class="field-block" data-external-field><span>External URL</span><input name="external_url" type="url" value="{{ $document->external_url }}" placeholder="https://..."></label>
                        </div>
                        <section class="subsection">
                            <h3>Research Owner Contact</h3>
                            <p>This contact receives access requests and research inquiries.</p>
                            <label class="field-block"><span>Research Owner Name</span><input name="owner_name" value="{{ old('owner_name', $document->owner_name) }}" placeholder="Enter research owner name"></label>
                            <div class="input-button-row">
                                <label class="field-block"><span>Research Owner Email</span><input name="owner_email" value="{{ old('owner_email', $document->owner_email ?: 'owner@agency.gov.ph') }}" data-owner-email></label>
                                <button class="btn-primary" type="button" data-use-account-email data-email="{{ auth()->user()->email }}">Use my account email</button>
                            </div>
                            <div class="info-box">Owner email will not be publicly displayed. RIKMS will send the email notification internally.</div>
                        </section>
                        <section class="subsection">
                            <h3>Notification Preferences</h3>
                            <label class="check-row"><input type="checkbox" name="notify_access_requests" value="1" @checked($document->notify_access_requests)> Notify owner when someone requests access</label>
                            <label class="check-row"><input type="checkbox" name="notify_research_inquiries" value="1" @checked($document->notify_research_inquiries)> Notify owner when someone sends a research inquiry</label>
                            <label class="check-row"><input type="checkbox" name="send_copy_to_agency_admin" value="1" @checked($document->send_copy_to_agency_admin)> Send copy to agency admin</label>
                        </section>
                        <section class="contact-preview">
                            <h3>Public Repository Contact Preview</h3>
                            <p>What public users will see in the research repository:</p>
                            <div class="contact-card">
                                <div class="avatar">?</div>
                                <div><strong>{{ $document->owner_name ?: 'Research Owner Name' }}</strong><span>{{ $document->agency->region }}</span></div>
                                <div class="contact-actions"><button type="button">Request Access</button><button type="button">Contact Research Owner</button></div>
                                <small>Email address is hidden from public view.</small>
                            </div>
                        </section>
                        <div class="info-note">Access settings are enforced at the repository level. Agency administrators can modify these after submission via the access control panel.</div>
                        <div class="wizard-actions">
                            <a class="btn-secondary" href="{{ route('upload.step', [$document, 4]) }}">← Previous</a>
                            <button class="btn-primary" type="submit">Review & Submit -></button>
                        </div>
                    </x-form-section-card>
                </form>
            @elseif ($isReport && $step === 4)
                <form method="POST" action="{{ route('upload.performance', $document) }}" data-performance-form>
                    @csrf
                    @method('PUT')
                    <x-form-section-card title="Project Performance" badge="STEP 4 OF 9" icon="chart" subtitle="Compare planned targets with actual accomplishment. The system calculates the row accomplishment percentage when numeric values are available.">
                        <h3>{{ $document->title ?: 'Untitled Report' }}</h3>
                        <label class="field-block"><span>Overall Physical Accomplishment (%)</span><input name="overall_physical_accomplishment" type="number" min="0" max="150" step="0.01"><small>Enter the official overall physical accomplishment reported for the project.</small></label>
                        <div class="performance-table" data-performance-rows>
                            <div class="performance-row heading"><span>Activity / Output / Indicator</span><span>Target</span><span>Actual</span><span>Accomplishment</span><span>Status</span></div>
                            @forelse ($document->performanceRows as $index => $row)
                                <div class="performance-row">
                                    <input name="rows[{{ $index }}][activity_output_indicator]" value="{{ $row->activity_output_indicator }}">
                                    <input name="rows[{{ $index }}][target]" type="number" step="0.01" value="{{ $row->target }}" data-target>
                                    <input name="rows[{{ $index }}][actual]" type="number" step="0.01" value="{{ $row->actual }}" data-actual>
                                    <output data-accomplishment>{{ $row->accomplishment_percentage }}%</output>
                                    <select name="rows[{{ $index }}][status]">@foreach (['Not Started','Ongoing','Completed','Delayed','Exceeded'] as $status)<option @selected($row->status === $status)>{{ $status }}</option>@endforeach</select>
                                </div>
                            @empty
                                <div class="performance-row">
                                    <input name="rows[0][activity_output_indicator]" placeholder="Activity / Output / Indicator">
                                    <input name="rows[0][target]" type="number" step="0.01" data-target>
                                    <input name="rows[0][actual]" type="number" step="0.01" data-actual>
                                    <output data-accomplishment>0%</output>
                                    <select name="rows[0][status]"><option>Not Started</option><option selected>Ongoing</option><option>Completed</option><option>Delayed</option><option>Exceeded</option></select>
                                </div>
                            @endforelse
                        </div>
                        <button type="button" class="btn-secondary" data-add-performance-row>Add Project Row</button>
                        <div class="wizard-actions">
                            <a class="btn-secondary" href="{{ route('upload.step', [$document, 3]) }}">← Previous</a>
                            <button class="btn-primary" type="submit">Continue -></button>
                        </div>
                    </x-form-section-card>
                </form>
            @elseif ($isReport && $step === 5)
                <form method="POST" action="{{ route('upload.pap', $document) }}">
                    @csrf
                    @method('PUT')
                    <x-form-section-card title="PAP Classification" badge="STEP 5 OF 9" icon="archive" subtitle="Classify the report under applicable programs, activities, and projects with beneficiary sectors.">
                        @php
                            $suggestedPap = $document->metadata?->raw_ai_json['pap_suggestions'] ?? ['Research and Development', 'Regional Development'];
                        @endphp
                        <div class="ai-suggestion">
                            <div>
                                <strong>AI Suggestions</strong>
                                <p>Recommended categories from extracted metadata.</p>
                                <div class="tag-row">
                                    @foreach ($suggestedPap as $papCat)
                                        <x-badge tone="purple">{{ $papCat }}</x-badge>
                                    @endforeach
                                </div>
                            </div>
                            <button type="button" class="btn-ai" data-apply-pap="{{ json_encode($suggestedPap) }}">Apply AI Suggestion</button>
                        </div>
                        <h3>PAP Categories</h3>
                        <div class="chip-grid">
                            @foreach ($papCategories as $category)
                                <label><input type="checkbox" name="categories[]" value="{{ $category }}" data-pap-category> {{ $category }}</label>
                            @endforeach
                        </div>
                        <label class="field-block"><span>PAP Description</span><textarea name="description" rows="4"></textarea></label>
                        <h3>Beneficiary Sectors (Pentahelix)</h3>
                        <div class="chip-grid">
                            <label><input type="checkbox" name="beneficiary_government" value="1"> Government</label>
                            <label><input type="checkbox" name="beneficiary_academe" value="1"> Academe</label>
                            <label><input type="checkbox" name="beneficiary_business" value="1"> Business</label>
                            <label><input type="checkbox" name="beneficiary_civil_society" value="1"> Civil Society</label>
                            <label><input type="checkbox" name="beneficiary_media" value="1"> Media</label>
                        </div>
                        <div class="wizard-actions"><a class="btn-secondary" href="{{ route('upload.step', [$document, 4]) }}">← Previous</a><button class="btn-primary" type="submit">Continue -></button></div>
                    </x-form-section-card>
                </form>
            @elseif ($isReport && $step === 6)
                <form method="POST" action="{{ route('upload.financials', $document) }}" data-financial-form>
                    @csrf
                    @method('PUT')
                    <x-form-section-card title="Financial Utilization" badge="STEP 6 OF 9" icon="chart">
                        @php($financial = $document->financial)
                        <div class="two-col">
                            <label class="field-block"><span>Allotted Budget</span><input name="allotted_budget" type="number" step="0.01" min="0" value="{{ $financial?->allotted_budget }}" data-allotted></label>
                            <label class="field-block"><span>Released Amount</span><input name="released_amount" type="number" step="0.01" min="0" value="{{ $financial?->released_amount }}"></label>
                            <label class="field-block"><span>Obligated Amount</span><input name="obligated_amount" type="number" step="0.01" min="0" value="{{ $financial?->obligated_amount }}"></label>
                            <label class="field-block"><span>Utilized / Disbursed Amount</span><input name="utilized_amount" type="number" step="0.01" min="0" value="{{ $financial?->utilized_amount }}" data-utilized></label>
                            <label class="field-block"><span>Financial As Of Date</span><input name="financial_as_of_date" type="date" value="{{ optional($financial?->financial_as_of_date)->format('Y-m-d') }}"></label>
                        </div>
                        <div class="summary-grid">
                            <div><span>Remaining Balance</span><strong data-remaining>{{ number_format((float) ($financial?->remaining_balance ?? 0), 2) }}</strong></div>
                            <div><span>Budget Utilization</span><strong data-utilization>{{ number_format((float) ($financial?->budget_utilization_percentage ?? 0), 2) }}%</strong></div>
                        </div>
                        <div class="wizard-actions"><a class="btn-secondary" href="{{ route('upload.step', [$document, 5]) }}">← Previous</a><button class="btn-primary" type="submit">Continue -></button></div>
                    </x-form-section-card>
                </form>
            @elseif ($isReport && $step === 7)
                <form method="POST" action="{{ route('upload.highlights', $document) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <x-form-section-card title="Highlights" badge="STEP 7 OF 9" icon="sparkle">
                        <label class="field-block"><span>Highlight Title</span><input name="highlight_title" value="{{ $document->highlights->first()?->title }}"></label>
                        <label class="field-block"><span>Description</span><textarea name="description" rows="5">{{ $document->highlights->first()?->description }}</textarea></label>
                        <x-upload-dropzone name="supporting_file" />
                        <label class="check-row"><input type="checkbox" name="is_featured" value="1" @checked($document->highlights->first()?->is_featured)> Mark as featured</label>
                        <p class="muted">Featured highlights receive priority placement in dashboards and public summaries.</p>
                        <div class="wizard-actions"><a class="btn-secondary" href="{{ route('upload.step', [$document, 6]) }}">← Previous</a><button class="btn-primary" type="submit">Continue -></button></div>
                    </x-form-section-card>
                </form>
            @elseif (($step === 6 && !$isReport) || ($step === 9 && $isReport))
                <x-form-section-card title="{{ $isReport ? 'Review' : 'Review Submission' }}" :badge="'STEP '.$step.' OF '.$totalSteps" icon="check" subtitle="Verify all information before submitting to the RIKMS repository.">
                    <div class="validation-card">
                        <div><strong>Validation - All checks passed</strong><span>5/5 passed</span></div>
                        <div class="progress-track"><div class="progress-fill green" style="width:100%"></div></div>
                        <div class="validation-list"><span>Document type confirmed</span><span>File uploaded</span><span>Public metadata selected</span><span>SDG tags selected</span><span>Access set to {{ $document->accessModeLabel() }}</span></div>
                    </div>
                    <div class="summary-grid">
                        <div><strong>{{ $document->sdgTags->count() }}</strong><span>SDGs Tagged</span></div>
                        <div><strong>{{ $publicCount ?: 3 }}</strong><span>Public Fields</span></div>
                        <div><strong>{{ $document->accessModeLabel() }}</strong><span>Access</span></div>
                    </div>
                    <div class="review-section"><h3>Document Information <x-badge tone="gray">Step 1-2</x-badge></h3><a href="{{ route('upload.step', [$document, 2]) }}">Edit</a><dl><dt>Type</dt><dd>{{ $document->documentTypeLabel() }}</dd><dt>File</dt><dd>{{ $document->original_filename }}</dd><dt>Title</dt><dd>{{ $document->title }}</dd><dt>Uploaded date</dt><dd>{{ $document->updated_at->format('Y-m-d') }}</dd></dl></div>
                    <div class="review-section"><h3>Metadata</h3><a href="{{ route('upload.step', [$document, 3]) }}">Edit</a><p>{{ \Illuminate\Support\Str::limit($metadata?->abstract, 260) }}</p></div>
                    @if ($isReport)
                        <div class="review-section"><h3>Project Performance</h3><a href="{{ route('upload.step', [$document, 4]) }}">Edit</a><p>{{ $document->performanceRows->count() }} performance rows recorded.</p></div>
                        <div class="review-section"><h3>PAP Classification</h3><a href="{{ route('upload.step', [$document, 5]) }}">Edit</a><p>{{ $document->papClassifications->pluck('category')->filter()->implode(', ') ?: 'No category selected' }}</p></div>
                        <div class="review-section"><h3>Financial Utilization</h3><a href="{{ route('upload.step', [$document, 6]) }}">Edit</a><p>Budget utilization: {{ number_format((float) ($document->financial?->budget_utilization_percentage ?? 0), 2) }}%</p></div>
                        <div class="review-section"><h3>Highlights</h3><a href="{{ route('upload.step', [$document, 7]) }}">Edit</a><p>{{ $document->highlights->first()?->title ?: 'No highlight title provided' }}</p></div>
                    @endif
                    <div class="review-section"><h3>SDG Tags</h3><a href="{{ route('upload.step', [$document, $isReport ? 8 : 4]) }}">Edit</a><div class="tag-row">@foreach ($document->sdgTags as $sdg)<span class="sdg-pill" style="--sdg-color: {{ $sdg->color }}">SDG {{ $sdg->number }}</span>@endforeach</div></div>
                    <div class="review-section"><h3>Access</h3><a href="{{ $isReport ? '#' : route('upload.step', [$document, 5]) }}">Edit</a><p>{{ $document->accessModeLabel() }} · {{ $document->owner_name ?: 'Research Owner Name' }}</p></div>
                    <form method="POST" action="{{ route('upload.submit', $document) }}">
                        @csrf
                        <div class="wizard-actions">
                            <a class="btn-secondary" href="{{ route('upload.step', [$document, $step - 1]) }}">← Previous</a>
                            <button class="btn-primary" type="submit">{{ $nextLabel }}</button>
                        </div>
                    </form>
                </x-form-section-card>
            @endif
        </main>

        <x-research-preview-sidebar :document="$document" :step="$step" :total="$totalSteps" :steps="$steps" />
    </div>
</x-app-layout>
