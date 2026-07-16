<?php

namespace Tests\Unit;

use App\Models\Document;
use App\Services\DoclingDocumentTextExtractor;
use App\Services\DocumentTextExtractionService;
use App\Services\GoogleCloudAccessTokenProvider;
use App\Services\MineruDocumentTextExtractor;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class DocumentTextExtractionSelectionTest extends TestCase
{
    private Document $document;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('documents');
        config()->set('rikms.documents_disk', 'documents');
        Storage::disk('documents')->put('papers/test.pdf', "%PDF-1.4\nsynthetic text");
        $this->document = new Document(['file_path' => 'papers/test.pdf']);
    }

    public function test_explicit_docling_selection_uses_only_docling(): void
    {
        config()->set('rikms.ai.local_extractor.driver', 'docling');
        $expected = ['method' => 'local_docling_markdown', 'text' => 'bounded Docling text'];
        $docling = Mockery::mock(DoclingDocumentTextExtractor::class);
        $docling->shouldReceive('configured')->once()->andReturnTrue();
        $docling->shouldReceive('extract')->once()->andReturn($expected);
        $mineru = Mockery::mock(MineruDocumentTextExtractor::class);
        $mineru->shouldNotReceive('configured');
        $mineru->shouldNotReceive('extract');

        $this->assertSame($expected, $this->service($docling, $mineru)->extract($this->document));
    }

    public function test_auto_mode_falls_through_configured_extractors_then_returns_mineru(): void
    {
        config()->set('rikms.ai.local_extractor.driver', 'auto');
        $expected = ['method' => 'local_mineru_markdown', 'text' => 'bounded MinerU text'];
        $docling = Mockery::mock(DoclingDocumentTextExtractor::class);
        $docling->shouldReceive('configured')->once()->andReturnTrue();
        $docling->shouldReceive('extract')->once()->andReturnNull();
        $mineru = Mockery::mock(MineruDocumentTextExtractor::class);
        $mineru->shouldReceive('configured')->once()->andReturnTrue();
        $mineru->shouldReceive('extract')->once()->andReturn($expected);

        $this->assertSame($expected, $this->service($docling, $mineru)->extract($this->document));
    }

    public function test_explicit_unconfigured_extractor_fails_honestly(): void
    {
        config()->set('rikms.ai.local_extractor.driver', 'mineru');
        $docling = Mockery::mock(DoclingDocumentTextExtractor::class);
        $docling->shouldNotReceive('configured');
        $mineru = Mockery::mock(MineruDocumentTextExtractor::class);
        $mineru->shouldReceive('configured')->once()->andReturnFalse();
        $mineru->shouldReceive('key')->once()->andReturn('mineru');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('selected but its executable is not configured');

        $this->service($docling, $mineru)->extract($this->document);
    }

    public function test_invalid_extractor_name_is_rejected_before_file_access(): void
    {
        config()->set('rikms.ai.local_extractor.driver', 'shell-command');
        $docling = Mockery::mock(DoclingDocumentTextExtractor::class);
        $docling->shouldNotReceive('configured');
        $mineru = Mockery::mock(MineruDocumentTextExtractor::class);
        $mineru->shouldNotReceive('configured');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('native, auto, docling, or mineru');

        $this->service($docling, $mineru)->extract($this->document);
    }

    private function service(
        DoclingDocumentTextExtractor $docling,
        MineruDocumentTextExtractor $mineru,
    ): DocumentTextExtractionService {
        return new DocumentTextExtractionService(
            Mockery::mock(GoogleCloudAccessTokenProvider::class),
            $docling,
            $mineru,
        );
    }
}
