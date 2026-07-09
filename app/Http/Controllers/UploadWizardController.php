<?php

namespace App\Http\Controllers;

use App\Http\Requests\SelectDocumentTypeRequest;
use App\Http\Requests\UpdateAccessControlRequest;
use App\Http\Requests\UpdateFinancialsRequest;
use App\Http\Requests\UpdateHighlightsRequest;
use App\Http\Requests\UpdateMetadataRequest;
use App\Http\Requests\UpdatePapRequest;
use App\Http\Requests\UpdatePerformanceRequest;
use App\Http\Requests\UpdatePublicFieldsRequest;
use App\Http\Requests\UpdateSdgTagsRequest;
use App\Http\Requests\UploadDocumentFileRequest;
use App\Models\Document;
use App\Models\PublicMetadataField;
use App\Models\SdgTag;
use App\Services\AiMetadataExtractionService;
use App\Services\AuditLogService;
use App\Services\DocumentReadinessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UploadWizardController extends Controller
{
    public function __construct(
        private readonly DocumentReadinessService $readiness,
        private readonly AuditLogService $audit,
    ) {
    }

    public function create()
    {
        return view('upload.new', [
            'steps' => $this->readiness->steps(null),
        ]);
    }

    public function selectType(SelectDocumentTypeRequest $request)
    {
        $user = Auth::user();
        $type = $request->validated('document_type');

        $document = Document::create([
            'agency_id' => $user->agency_id,
            'uploaded_by' => $user->id,
            'document_type' => $type,
            'title' => null,
            'status' => 'draft',
            'year' => now()->year,
            'category' => $type === Document::TERMINAL_REPORT ? 'Terminal Report' : 'Uncategorized',
            'access_mode' => 'public_download',
            'owner_email' => $user->agency?->contact_email ?? $user->email,
            'completion_score' => 0,
            'digital_library_score' => 70,
        ]);

        $this->audit->log('document created', $document, ['document_type' => $type], $request);

        return redirect()->route('upload.step', [$document, 2]);
    }

    public function show(Document $document, int $step)
    {
        $this->authorize('update', $document);

        $document->load(['metadata', 'publicFields', 'sdgTags', 'agency', 'performanceRows', 'financial', 'papClassifications', 'highlights']);
        $totalSteps = $this->readiness->stepCount($document);
        abort_if($step < 1 || $step > $totalSteps, 404);

        return view('upload.step', [
            'document' => $document,
            'step' => $step,
            'totalSteps' => $totalSteps,
            'steps' => $this->readiness->steps($document),
            'allSdgs' => SdgTag::orderBy('number')->get(),
            'papCategories' => $this->papCategories(),
            'metadataFields' => $this->metadataFields(),
            'publicFieldNames' => $document->publicFields->where('is_public', true)->pluck('field_name')->all(),
        ]);
    }

    public function storeFile(UploadDocumentFileRequest $request, Document $document)
    {
        $this->authorize('update', $document);
        $validated = $request->validated();

        $updates = [
            'title' => $validated['manual_title'] ?: $document->title,
            'description' => $validated['description'] ?? $document->description,
            'year' => $validated['reporting_year'] ?? $document->year ?? now()->year,
            'completion_score' => max($document->completion_score, 20),
        ];

        if ($request->hasFile('document_file')) {
            $file = $request->file('document_file');
            $path = $file->store('research-documents');

            $updates += [
                'file_path' => $path,
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
            ];
        }

        $document->update($updates);

        if ($request->hasFile('document_file')) {
            \App\Jobs\ProcessDocumentJob::dispatch($document);
        }

        $this->audit->log('file uploaded', $document, ['filename' => $document->original_filename], $request);

        return redirect()->route('upload.step', [$document, 3]);
    }

    public function runAiAnalysis(Request $request, Document $document, AiMetadataExtractionService $ai)
    {
        $this->authorize('update', $document);

        $result = $ai->analyze($document);

        $document->metadata()->updateOrCreate(
            ['document_id' => $document->id],
            [
                'title' => $result['title'],
                'abstract' => $result['abstract'],
                'methodology' => $result['methodology'],
                'review_of_related_literature' => $result['review_of_related_literature'],
                'theoretical_framework' => $result['theoretical_framework'],
                'results_and_discussion' => $result['results_and_discussion'],
                'keywords' => $result['keywords'],
                'authors' => $result['authors'],
                'doi' => $result['doi'],
                'ai_confidence' => 0.88,
                'raw_ai_json' => $result,
            ]
        );

        $document->update([
            'title' => $document->title ?: $result['title'],
            'is_ai_tagged' => true,
            'completion_score' => max($document->completion_score, 60),
            'digital_library_score' => 70,
        ]);

        $this->syncPublicFields($document, ['title', 'abstract', 'methodology', 'results_and_discussion']);
        $this->audit->log('AI analysis run', $document, ['suggested_sdgs' => $result['suggested_sdgs']], $request);

        return redirect()->route('upload.step', [$document, 3])->with('status', 'Metadata extracted successfully');
    }

    public function updateMetadata(UpdateMetadataRequest $request, Document $document)
    {
        $this->authorize('update', $document);
        $validated = $request->validated();

        $document->metadata()->updateOrCreate(
            ['document_id' => $document->id],
            [
                'title' => $validated['title'] ?? null,
                'abstract' => $validated['abstract'] ?? null,
                'methodology' => $validated['methodology'] ?? null,
                'review_of_related_literature' => $validated['review_of_related_literature'] ?? null,
                'theoretical_framework' => $validated['theoretical_framework'] ?? null,
                'results_and_discussion' => $validated['results_and_discussion'] ?? null,
                'keywords' => $this->splitList($validated['keywords'] ?? ''),
                'authors' => $this->splitList($validated['authors'] ?? ''),
            ]
        );

        $document->update([
            'title' => $validated['title'] ?? $document->title,
            'completion_score' => max($document->completion_score, 70),
        ]);

        $this->syncPublicFields($document, $validated['public_fields'] ?? []);
        $this->audit->log('metadata edited', $document, [], $request);

        return redirect()->route('upload.step', [$document, 4]);
    }

    public function updatePublicFields(UpdatePublicFieldsRequest $request, Document $document)
    {
        $this->authorize('update', $document);
        $this->syncPublicFields($document, $request->validated('public_fields') ?? []);
        $this->audit->log('public metadata fields updated', $document, [], $request);

        return back()->with('status', 'Public metadata fields updated.');
    }

    public function updateSdgTags(UpdateSdgTagsRequest $request, Document $document)
    {
        $this->authorize('update', $document);
        $numbers = $request->validated('selected_sdgs');
        $tags = SdgTag::whereIn('number', $numbers)->get();
        $sync = $tags->mapWithKeys(fn (SdgTag $tag) => [$tag->id => ['source' => 'manual', 'confidence' => null]])->all();

        $document->sdgTags()->sync($sync);
        $document->update(['completion_score' => max($document->completion_score, 85)]);
        $this->audit->log('SDG tags updated', $document, ['sdgs' => $numbers], $request);

        return redirect()->route('upload.step', [$document, $document->usesReportFlow() ? 9 : 5]);
    }

    public function updateAccessControl(UpdateAccessControlRequest $request, Document $document)
    {
        $this->authorize('update', $document);
        $validated = $request->validated();

        $document->update([
            'access_mode' => $validated['access_mode'],
            'embargo_until' => $validated['embargo_until'] ?? null,
            'external_url' => $validated['external_url'] ?? null,
            'owner_name' => $validated['owner_name'] ?? null,
            'owner_email' => $validated['owner_email'] ?? null,
            'notify_access_requests' => $request->boolean('notify_access_requests'),
            'notify_research_inquiries' => $request->boolean('notify_research_inquiries'),
            'send_copy_to_agency_admin' => $request->boolean('send_copy_to_agency_admin'),
            'completion_score' => max($document->completion_score, 95),
        ]);

        $this->audit->log('access control updated', $document, ['access_mode' => $document->access_mode], $request);

        return redirect()->route('upload.step', [$document, 6]);
    }

    public function updatePerformance(UpdatePerformanceRequest $request, Document $document)
    {
        $this->authorize('update', $document);
        $document->performanceRows()->delete();

        foreach ($request->validated('rows') ?? [] as $row) {
            if (blank($row['activity_output_indicator'] ?? null)) {
                continue;
            }

            $target = $row['target'] ?? null;
            $actual = $row['actual'] ?? null;
            $percentage = $target && $actual !== null ? round(((float) $actual / (float) $target) * 100, 2) : null;

            $document->performanceRows()->create([
                'activity_output_indicator' => $row['activity_output_indicator'],
                'target' => $target,
                'actual' => $actual,
                'accomplishment_percentage' => $percentage,
                'status' => $row['status'] ?? 'Ongoing',
            ]);
        }

        $document->update(['completion_score' => max($document->completion_score, 72)]);
        $this->audit->log('performance updated', $document, [], $request);

        return redirect()->route('upload.step', [$document, 5]);
    }

    public function updatePap(UpdatePapRequest $request, Document $document)
    {
        $this->authorize('update', $document);
        $validated = $request->validated();
        $document->papClassifications()->delete();

        foreach (($validated['categories'] ?? ['Research and Development']) as $category) {
            $document->papClassifications()->create([
                'category' => $category,
                'description' => $validated['description'] ?? null,
                'beneficiary_government' => $request->boolean('beneficiary_government'),
                'beneficiary_academe' => $request->boolean('beneficiary_academe'),
                'beneficiary_business' => $request->boolean('beneficiary_business'),
                'beneficiary_civil_society' => $request->boolean('beneficiary_civil_society'),
                'beneficiary_media' => $request->boolean('beneficiary_media'),
            ]);
        }

        $document->update(['completion_score' => max($document->completion_score, 78)]);
        $this->audit->log('PAP classification updated', $document, ['categories' => $validated['categories'] ?? []], $request);

        return redirect()->route('upload.step', [$document, 6]);
    }

    public function updateFinancials(UpdateFinancialsRequest $request, Document $document)
    {
        $this->authorize('update', $document);
        $validated = $request->validated();
        $allotted = (float) ($validated['allotted_budget'] ?? 0);
        $utilized = (float) ($validated['utilized_amount'] ?? 0);

        $document->financial()->updateOrCreate(
            ['document_id' => $document->id],
            [
                'allotted_budget' => $validated['allotted_budget'] ?? null,
                'released_amount' => $validated['released_amount'] ?? null,
                'obligated_amount' => $validated['obligated_amount'] ?? null,
                'utilized_amount' => $validated['utilized_amount'] ?? null,
                'remaining_balance' => $allotted ? $allotted - $utilized : null,
                'budget_utilization_percentage' => $allotted ? round(($utilized / $allotted) * 100, 2) : null,
                'financial_as_of_date' => $validated['financial_as_of_date'] ?? null,
            ]
        );

        $document->update(['completion_score' => max($document->completion_score, 82)]);
        $this->audit->log('financials updated', $document, [], $request);

        $redirect = redirect()->route('upload.step', [$document, 7]);

        return $utilized > $allotted && $allotted > 0
            ? $redirect->with('warning', 'Utilized amount exceeds allotted budget. Review before submission.')
            : $redirect;
    }

    public function updateHighlights(UpdateHighlightsRequest $request, Document $document)
    {
        $this->authorize('update', $document);
        $validated = $request->validated();
        $filePath = null;

        if ($request->hasFile('supporting_file')) {
            $filePath = $request->file('supporting_file')->store('highlight-attachments');
        }

        $document->highlights()->updateOrCreate(
            ['document_id' => $document->id],
            [
                'title' => $validated['highlight_title'] ?? null,
                'description' => $validated['description'] ?? null,
                'file_path' => $filePath,
                'is_featured' => $request->boolean('is_featured'),
            ]
        );

        $document->update(['completion_score' => max($document->completion_score, 88)]);
        $this->audit->log('highlights updated', $document, [], $request);

        return redirect()->route('upload.step', [$document, 8]);
    }

    public function submit(Request $request, Document $document)
    {
        $this->authorize('update', $document);

        $document->update([
            'status' => 'pending',
            'submitted_at' => now(),
            'completion_score' => 100,
        ]);

        $this->audit->log('submitted for review', $document, [], $request);

        return redirect()->route('upload.success', $document);
    }

    public function success(Document $document)
    {
        $this->authorize('view', $document);
        $document->load(['sdgTags', 'metadata']);

        return view('upload.success', ['document' => $document]);
    }

    private function syncPublicFields(Document $document, array $publicFields): void
    {
        foreach (array_keys($this->metadataFields()) as $field) {
            PublicMetadataField::updateOrCreate(
                ['document_id' => $document->id, 'field_name' => $field],
                ['is_public' => in_array($field, $publicFields, true)]
            );
        }
    }

    private function splitList(string $value): array
    {
        return collect(explode(',', $value))
            ->map(fn (string $item) => trim($item))
            ->filter()
            ->values()
            ->all();
    }

    private function metadataFields(): array
    {
        return [
            'title' => 'Title',
            'abstract' => 'Abstract',
            'methodology' => 'Methodology',
            'review_of_related_literature' => 'Review of Related Literature',
            'theoretical_framework' => 'Theoretical Framework',
            'results_and_discussion' => 'Results and Discussion',
        ];
    }

    private function papCategories(): array
    {
        return [
            'Research and Development',
            'Regional Development',
            'Science and Technology Services',
            'Technology Transfer',
            'Innovation Support',
            'Community Development',
            'Digital Economy',
            'Sustainable Energy',
            'Agriculture and Fisheries',
            'Health Research',
            'Disaster Risk Reduction',
            'Education and Training',
        ];
    }
}
