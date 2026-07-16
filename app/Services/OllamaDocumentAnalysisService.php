<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class OllamaDocumentAnalysisService implements DocumentAnalysisDriver
{
    public function analyze(Document $document): array
    {
        $this->assertDoclingRunning();

        $python = config('rikms.ai.ollama.python_path', 'python');
        $script = base_path('scripts/paper_metadata_extractor.py');
        $pdfPath = Storage::disk('documents')->path($document->file_path);

        $tempOutput = tempnam(sys_get_temp_dir(), 'rikms-ollama-');

        $process = Process::timeout(config('rikms.ai.timeout_seconds', 300))->run([
            $python,
            $script,
            '--file', $pdfPath,
            '--action', 'extract',
            '--output', $tempOutput,
            '--model', config('rikms.ai.ollama.model', 'gemma2:2b'),
        ]);

        if (! $process->successful()) {
            throw new RuntimeException('Ollama extraction failed: ' . $process->errorOutput());
        }

        $json = file_get_contents($tempOutput);
        @unlink($tempOutput);

        $suggestions = json_decode($json, true);
        if (! is_array($suggestions)) {
            throw new RuntimeException('Failed to parse extraction output.');
        }

        $needsOcr = (bool) ($suggestions['needs_ocr'] ?? false);
        unset($suggestions['needs_ocr']); // keep suggestions clean for the schema validator

        return [
            'suggestions'       => $suggestions,
            'extraction_method' => $needsOcr ? 'local_gemma_rag_docling_ocr' : 'local_gemma_rag_docling',
            'needs_ocr'         => $needsOcr,
            'input_tokens'      => 0,
            'output_tokens'     => 0,
            'reasoning_tokens'  => 0,
            'estimated_cost_usd' => 0.00,
        ];
    }

    /**
     * Ping the Docling server health endpoint. Throws a clear error if it is not running
     * so the queue job fails fast rather than timing out after 300 seconds.
     */
    private function assertDoclingRunning(): void
    {
        $base = rtrim((string) config('rikms.ai.docling.base_url', 'http://127.0.0.1:5001'), '/');
        try {
            $response = Http::timeout(3)->get("{$base}/health");
            if ($response->successful()) {
                return;
            }
        } catch (\Throwable $e) {
            // fall through to throw below
        }
        throw new RuntimeException(
            'Docling server is not running. Start it first: python scripts/docling_server.py'
        );
    }
}
