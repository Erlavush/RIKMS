<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaperParserService
{
    protected string $mineruEnvPath;
    protected string $ollamaUrl;
    protected string $ollamaModel;

    public function __construct()
    {
        $this->mineruEnvPath = env('MINERU_ENV_PATH', '/home/usepobrero_user575/mineru_env/bin/mineru');
        $this->ollamaUrl = env('OLLAMA_URL', 'http://localhost:11434');
        $this->ollamaModel = env('OLLAMA_MODEL', 'qwen3.5:4b');
    }

    /**
     * Parses a PDF and extracts structured sections.
     */
    public function parse(string $pdfPath): array
    {
        // 1. Define temporary output directory
        $outputDir = storage_path('app/temp_parser_outputs/' . Str::random(10));
        if (!file_exists($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        try {
            // 2. Execute MinerU CLI via the virtualenv
            $processResult = Process::timeout(300)->run([
                $this->mineruEnvPath,
                '-p', $pdfPath,
                '-o', $outputDir,
                '-b', 'pipeline'
            ]);

            if (!$processResult->successful()) {
                throw new \Exception("MinerU failed: " . $processResult->errorOutput());
            }

            // 3. Find the generated markdown file recursively
            $markdownPath = $this->findMarkdownFile($outputDir);

            if (!$markdownPath) {
                // Log directory contents for debugging purposes
                $this->logDirContents($outputDir);
                throw new \Exception("Markdown file (.md) was not generated inside the output directory. Check storage/logs/laravel.log for folder contents.");
            }

            $rawContent = file_get_contents($markdownPath);

            // 4. Extract only what we need to avoid token limits.
            $sampleText = $this->extractKeySections($rawContent);

            // 5. Query Ollama for structured metadata
            return $this->extractMetadataWithOllama($sampleText);

        } finally {
            // Clean up temporary folders
            if (file_exists($outputDir)) {
                $this->deleteDir($outputDir);
            }
        }
    }

    /**
     * Recursively search for the first .md file in the directory.
     */
    protected function findMarkdownFile(string $dir): ?string
    {
        if (!is_dir($dir)) {
            return null;
        }

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === 'md') {
                return $file->getRealPath();
            }
        }
        return null;
    }

    /**
     * Helper to log directory contents to Laravel logs if things fail.
     */
    protected function logDirContents(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($files as $name => $object) {
            Log::info("MinerU Output Dir item: " . $name);
        }
    }

    /**
     * Selectively extracts the head of the paper and locates the Methods section.
     */
    protected function extractKeySections(string $markdownContent): string
    {
        // 1. Grab the head of the paper (captures Title, Authors, Abstract, Keywords, Intro start)
        $head = \Illuminate\Support\Str::limit($markdownContent, 8000, '');

        // 2. Scan the rest of the document for a Methods or Methodology section
        $methodsContent = '';

        // Matches markdown headers like: ## Methods, ## Methodology, ## 2. Methods, ### Methodology, etc.
        $pattern = '/^(#{2,3}\s+(?:\d+\.?\s+)?(?:methods|methodology|materials\s+and\s+methods|research\s+design))/im';

        if (preg_match($pattern, $markdownContent, $matches, PREG_OFFSET_CAPTURE)) {
            $offset = $matches[0][1];
            // Grab up to 4,000 characters starting from the matched Methods heading
            $methodsContent = substr($markdownContent, $offset, 4000);
        }

        if (!empty($methodsContent)) {
            return "{$head}\n\n---\n[EXTRACTED METHODS SECTION]\n{$methodsContent}";
        }

        // Fallback to head if no explicit methods section is found
        return $head;
    }

/**
     * Queries Ollama using Structured Outputs (JSON Schema) to guarantee valid extraction.
     */
    protected function extractMetadataWithOllama(string $text): array
    {
        // Define a strict JSON schema
        $schema = [
            'type' => 'object',
            'properties' => [
                'title' => ['type' => 'string'],
                'authors' => [
                    'type' => 'array',
                    'items' => ['type' => 'string']
                ],
                'abstract' => ['type' => 'string'],
                'keywords' => [
                    'type' => 'array',
                    'items' => ['type' => 'string']
                ],
                'introduction' => ['type' => 'string'],
                'methods' => ['type' => 'string']
            ],
            'required' => ['title', 'authors', 'abstract', 'keywords', 'introduction', 'methods']
        ];

        // Explicitly instruct the model via system role to only output the JSON object.
        // This resolves the conflict where conversational preambles cause blank outputs.
        $systemInstructions = <<<EOT
You are an expert academic paper analyzer. You MUST respond with a single valid JSON object that matches the requested schema structure.
Do not output any introductory or concluding conversational text, and do not wrap your response in markdown code blocks.
EOT;

        $response = Http::timeout(600)->post("{$this->ollamaUrl}/api/generate", [
            'model' => $this->ollamaModel,
            'system' => $systemInstructions, // High-priority system prompt
            'prompt' => "Here is the paper text to analyze:\n\n{$text}",
            'format' => $schema,            // Enforces strict schema compilation
            'stream' => false,
            'options' => [
                'num_predict' => 4096,
                'num_ctx' => 8192,
            ]
        ]);

        if ($response->failed()) {
            throw new \Exception("Ollama API request failed: " . $response->body());
        }

        $result = $response->json();
        $rawResponse = $result['response'] ?? '';

        if (empty(trim($rawResponse))) {
            throw new \Exception("Ollama returned an empty response. Verify that your Ollama server is running and the model is fully loaded.");
        }

        $decoded = json_decode($rawResponse, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            \Illuminate\Support\Facades\Log::error("Failed to decode JSON from Ollama. Error: " . json_last_error_msg());
            \Illuminate\Support\Facades\Log::error("Raw Ollama Response: " . $rawResponse);

            throw new \Exception("Ollama returned invalid JSON. Check storage/logs/laravel.log for the raw response.");
        }

        return $decoded;
    }

    /**
     * Helper to recursively clean directories.
     */
    protected function deleteDir($dirPath): void
    {
        if (!is_dir($dirPath)) {
            return;
        }
        $files = array_diff(scandir($dirPath), ['.', '..']);
        foreach ($files as $file) {
            (is_dir("$dirPath/$file")) ? $this->deleteDir("$dirPath/$file") : unlink("$dirPath/$file");
        }
        rmdir($dirPath);
    }
}
