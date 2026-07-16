<?php

namespace Tests\Unit;

use App\Services\LocalExtractorProcessRunner;
use App\Services\MineruDocumentTextExtractor;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class MineruDocumentTextExtractorTest extends TestCase
{
    private string $source;

    protected function setUp(): void
    {
        parent::setUp();

        $this->source = tempnam(sys_get_temp_dir(), 'rikms-mineru-source-').'.pdf';
        file_put_contents($this->source, "%PDF-1.4\nsynthetic test document");
        config()->set('rikms.ai.local_extractor.mineru.command', PHP_BINARY);
        config()->set('rikms.ai.local_extractor.max_output_bytes', 100000);
        config()->set('rikms.ai.local_extractor.timeout_seconds', 45);
        config()->set('rikms.ai.max_text_characters', 2000);
        config()->set('rikms.ai.minimum_embedded_text_characters', 10);
    }

    protected function tearDown(): void
    {
        @unlink($this->source);
        parent::tearDown();
    }

    public function test_it_runs_mineru_with_safe_arguments_selects_markdown_and_cleans_up(): void
    {
        $workingDirectory = null;
        $runner = Mockery::mock(LocalExtractorProcessRunner::class);
        $runner->shouldReceive('run')->once()->andReturnUsing(function (array $command, int $timeout) use (&$workingDirectory): bool {
            $this->assertSame(PHP_BINARY, $command[0]);
            $this->assertSame(['-p', $this->source], array_slice($command, 1, 2));
            $this->assertSame('pipeline', $command[array_search('-b', $command, true) + 1]);
            $this->assertSame(45, $timeout);

            $workingDirectory = $command[array_search('-o', $command, true) + 1];
            mkdir($workingDirectory.DIRECTORY_SEPARATOR.'paper', 0700, true);
            file_put_contents($workingDirectory.DIRECTORY_SEPARATOR.'small.md', 'short');
            file_put_contents(
                $workingDirectory.DIRECTORY_SEPARATOR.'paper'.DIRECTORY_SEPARATOR.'paper.md',
                str_repeat('MinerU methodology and table text. ', 30),
            );

            return true;
        });

        $result = (new MineruDocumentTextExtractor($runner))->extract($this->source);

        $this->assertSame('local_mineru_markdown', $result['method']);
        $this->assertStringContainsString('MinerU methodology and table text.', $result['text']);
        $this->assertNotNull($workingDirectory);
        $this->assertDirectoryDoesNotExist($workingDirectory);
    }

    public function test_it_rejects_an_oversized_output_tree_and_cleans_up(): void
    {
        config()->set('rikms.ai.local_extractor.max_output_bytes', 4096);
        $workingDirectory = null;
        $runner = Mockery::mock(LocalExtractorProcessRunner::class);
        $runner->shouldReceive('run')->once()->andReturnUsing(function (array $command) use (&$workingDirectory): bool {
            $workingDirectory = $command[array_search('-o', $command, true) + 1];
            file_put_contents($workingDirectory.DIRECTORY_SEPARATOR.'paper.md', str_repeat('x', 5000));

            return true;
        });

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('exceeded');

        try {
            (new MineruDocumentTextExtractor($runner))->extract($this->source);
        } finally {
            $this->assertNotNull($workingDirectory);
            $this->assertDirectoryDoesNotExist($workingDirectory);
        }
    }
}
