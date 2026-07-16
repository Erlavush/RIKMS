<?php

namespace App\Services;

use App\Models\Document;

interface DocumentAnalysisDriver
{
    /** @return array<string, mixed> */
    public function analyze(Document $document): array;
}
