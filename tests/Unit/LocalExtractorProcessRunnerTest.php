<?php

namespace Tests\Unit;

use App\Services\LocalExtractorProcessRunner;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class LocalExtractorProcessRunnerTest extends TestCase
{
    public function test_it_returns_the_real_process_exit_state(): void
    {
        $runner = new LocalExtractorProcessRunner;

        $this->assertTrue($runner->run([PHP_BINARY, '-r', 'exit(0);'], 5));
        $this->assertFalse($runner->run([PHP_BINARY, '-r', 'exit(7);'], 5));
    }

    public function test_it_rejects_control_characters_before_starting_a_process(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new LocalExtractorProcessRunner)->run([PHP_BINARY, "bad\0argument"], 5);
    }
}
