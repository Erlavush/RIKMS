<?php

namespace App\Services;

use App\Contracts\LocalDocumentTextExtractor;
use FilesystemIterator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use Throwable;

class MineruDocumentTextExtractor implements LocalDocumentTextExtractor
{
    public function __construct(
        private readonly LocalExtractorProcessRunner $processes,
    ) {}

    public function key(): string
    {
        return 'mineru';
    }

    public function configured(): bool
    {
        return trim((string) config('rikms.ai.local_extractor.mineru.command')) !== '';
    }

    /** @return array{method: string, text: string}|null */
    public function extract(string $pdfPath): ?array
    {
        if (! $this->configured()) {
            return null;
        }

        $command = $this->regularFile((string) config('rikms.ai.local_extractor.mineru.command'), 'MinerU executable');
        $source = $this->regularFile($pdfPath, 'PDF source');
        $files = new Filesystem;
        $workingDirectory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'rikms-mineru-'.Str::uuid();

        try {
            $files->makeDirectory($workingDirectory, 0700, true, true);
            if (! $this->processes->run([
                $command,
                '-p', $source,
                '-o', $workingDirectory,
                '-b', 'pipeline',
            ], (int) config('rikms.ai.local_extractor.timeout_seconds'))) {
                return null;
            }

            $markdownPath = $this->findMarkdown($workingDirectory);
            if ($markdownPath === null) {
                return null;
            }

            $contents = file_get_contents($markdownPath);
            $text = $this->normalize(is_string($contents) ? $contents : '');
            if (mb_strlen($text) < (int) config('rikms.ai.minimum_embedded_text_characters')) {
                return null;
            }

            return [
                'method' => 'local_mineru_markdown',
                'text' => mb_substr($text, 0, $this->maximumCharacters()),
            ];
        } catch (Throwable $exception) {
            if ($exception instanceof RuntimeException) {
                throw $exception;
            }

            return null;
        } finally {
            $files->deleteDirectory($workingDirectory);
        }
    }

    private function findMarkdown(string $workingDirectory): ?string
    {
        $realWorkingDirectory = realpath($workingDirectory);
        if ($realWorkingDirectory === false) {
            return null;
        }

        $totalBytes = 0;
        $maximumBytes = $this->maximumOutputBytes();
        /** @var list<array{path: string, size: int}> $candidates */
        $candidates = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($realWorkingDirectory, FilesystemIterator::SKIP_DOTS),
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isLink()) {
                throw new RuntimeException('MinerU output must not contain symbolic links.');
            }
            if (! $file->isFile()) {
                continue;
            }

            $size = max(0, $file->getSize());
            $totalBytes += $size;
            if ($totalBytes > $maximumBytes) {
                throw new RuntimeException('MinerU output exceeded the configured limit.');
            }
            if (strtolower($file->getExtension()) !== 'md') {
                continue;
            }

            $realPath = $file->getRealPath();
            if ($realPath === false || ! $this->isInside($realPath, $realWorkingDirectory)) {
                throw new RuntimeException('MinerU wrote outside its private working directory.');
            }

            $candidates[] = ['path' => $realPath, 'size' => $size];
        }

        usort($candidates, fn (array $left, array $right): int => [$right['size'], $left['path']] <=> [$left['size'], $right['path']]);

        return $candidates[0]['path'] ?? null;
    }

    private function isInside(string $path, string $directory): bool
    {
        $prefix = rtrim($directory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        return str_starts_with($path, $prefix);
    }

    private function regularFile(string $path, string $label): string
    {
        $path = trim($path);
        if ($path === '' || preg_match('/[\x00-\x1F\x7F]/', $path)) {
            throw new RuntimeException($label.' path is invalid.');
        }

        $resolved = realpath($path);
        if ($resolved === false || ! is_file($resolved) || is_link($path)) {
            throw new RuntimeException($label.' must be an existing regular file.');
        }

        return $resolved;
    }

    private function maximumCharacters(): int
    {
        return max(1000, min(600000, (int) config('rikms.ai.max_text_characters')));
    }

    private function maximumOutputBytes(): int
    {
        return max(4096, min(4_000_000, (int) config('rikms.ai.local_extractor.max_output_bytes')));
    }

    private function normalize(string $text): string
    {
        $text = str_replace("\0", '', $text);
        $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/\R{3,}/u', "\n\n", $text) ?? $text;

        return trim($text);
    }
}
