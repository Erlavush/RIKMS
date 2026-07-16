<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PaperParserService;

class TestPaperParser extends Command
{
    protected $signature = 'paper:parse {file : Absolute path to the PDF file}';
    protected $description = 'Test-parse a PDF using MinerU and Ollama';

    public function handle(PaperParserService $parser)
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return Command::FAILURE;
        }

        $this->info("Starting MinerU PDF layout extraction...");

        try {
            $data = $parser->parse($filePath);

            $this->info("Extraction Successful!");
            $this->line(json_encode($data, JSON_PRETTY_PRINT));

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to parse paper: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
