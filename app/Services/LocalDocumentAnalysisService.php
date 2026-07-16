<?php

namespace App\Services;

use App\Models\Document;
use App\Support\DocumentStorage;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class LocalDocumentAnalysisService
{
    public function __construct(
        private readonly PaperParserService $parser,
    ) {}

    /**
     * Analyzes the document locally using MinerU and Ollama.
     * Matches the exact return signature of VertexDocumentAnalysisService.
     */
    public function analyze(Document $document): array
    {
        // 1. Resolve the absolute path of the physical PDF inside WSL/Ubuntu
        $disk = Storage::disk(DocumentStorage::disk());
        $filePath = (string) $document->file_path;

        if (!$disk->exists($filePath)) {
            throw new RuntimeException("PDF file not found in storage: {$filePath}");
        }

        $absolutePath = $disk->path($filePath);

        // 2. Process the PDF locally via MinerU and Ollama
        $localResult = $this->parser->parse($absolutePath);

        // 3. Map local results to the strict database schema, filling other required fields with defaults
        $suggestions = [
            'title' => $localResult['title'] ?? 'Not Found',
            'abstract' => $localResult['abstract'] ?? 'Not Found',
            'methodology' => $localResult['methods'] ?? 'Not Found', // Map "methods" to database "methodology"
            'review_of_related_literature' => '',
            'theoretical_framework' => '',
            'results_and_discussion' => '',
            'keywords' => $localResult['keywords'] ?? [],
            'authors' => $localResult['authors'] ?? [],
            'doi' => '',
            'category' => '',
            'executive_summary' => $localResult['introduction'] ?? '', // Map introduction summary to executive summary
            'recommendations' => [],
            'suggested_sdgs' => [],
            'overall_confidence' => 0.85, // Fallback confidence score
            'evidence_pages' => [],
        ];

        // 4. Validate output so it is guaranteed to insert cleanly into the database
        $suggestions = $this->validatedSuggestions($suggestions);

        return [
            'suggestions' => $suggestions,
            'extraction_method' => 'mineru_ollama_local',
            'input_tokens' => 0,          // Local models do not charge tokens
            'output_tokens' => 0,
            'reasoning_tokens' => 0,
            'estimated_cost_usd' => 0.00,  // Completely free
        ];
    }

    /**
     * Schema validator identical to the Vertex service to keep your database consistent.
     */
    private function validatedSuggestions(array $suggestions): array
    {
        $validator = validator($suggestions, [
            'title' => ['present', 'string', 'max:500'],
            'abstract' => ['present', 'string', 'max:20000'],
            'methodology' => ['present', 'string', 'max:30000'],
            'review_of_related_literature' => ['present', 'string', 'max:30000'],
            'theoretical_framework' => ['present', 'string', 'max:30000'],
            'results_and_discussion' => ['present', 'string', 'max:30000'],
            'keywords' => ['present', 'array', 'max:100'],
            'keywords.*' => ['string', 'max:255'],
            'authors' => ['present', 'array', 'max:100'],
            'authors.*' => ['string', 'max:500'],
            'doi' => ['present', 'string', 'max:255'],
            'category' => ['present', 'string', 'max:255'],
            'executive_summary' => ['present', 'string', 'max:10000'],
            'recommendations' => ['present', 'array', 'max:30'],
            'recommendations.*' => ['string', 'max:2000'],
            'suggested_sdgs' => ['present', 'array', 'max:17'],
            'suggested_sdgs.*.number' => ['required', 'integer', 'between:1,17'],
            'suggested_sdgs.*.reason' => ['required', 'string', 'max:2000'],
            'suggested_sdgs.*.confidence' => ['required', 'numeric', 'between:0,1'],
            'overall_confidence' => ['required', 'numeric', 'between:0,1'],
            'evidence_pages' => ['present', 'array', 'max:100'],
            'evidence_pages.*' => ['integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages(['ai_response' => 'Local parser response failed RIKMS schema validation.']);
        }

        return Arr::only($validator->validated(), [
            'title', 'abstract', 'methodology', 'review_of_related_literature',
            'theoretical_framework', 'results_and_discussion', 'keywords', 'authors',
            'doi', 'category', 'executive_summary', 'recommendations', 'suggested_sdgs',
            'overall_confidence', 'evidence_pages',
        ]);
    }
}
