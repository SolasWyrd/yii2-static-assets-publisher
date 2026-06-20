<?php

declare(strict_types=1);

namespace SolasWyrd\Yii2StaticAssets\Hash;

use FilesystemIterator;
use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

/**
 * Generates a deterministic asset directory name from the source path and
 * the actual contents of all files inside it.
 *
 * The result changes automatically when a file is added, removed, renamed,
 * or its contents change. No Git revision or environment variable is needed.
 */
final class ContentAssetPathHasher
{
    /** @var array<string, string> */
    private array $roots;

    /** @var array<string, string> */
    private array $cache = [];

    /** @param array<string, string> $roots */
    public function __construct(
        array $roots,
        private readonly int $length = 16,
    ) {
        if ($length < 8 || $length > 64) {
            throw new InvalidArgumentException('Hash length must be between 8 and 64.');
        }

        $normalizedRoots = [];

        foreach ($roots as $name => $path) {
            if ($name === '' || $path === '') {
                throw new InvalidArgumentException('Root name and path cannot be empty.');
            }

            $normalizedRoots[$name] = $this->normalizePath($path);
        }

        \uasort(
            $normalizedRoots,
            static fn (string $left, string $right): int => \strlen($right) <=> \strlen($left),
        );

        $this->roots = $normalizedRoots;
    }

    public function __invoke(string $sourcePath): string
    {
        $normalizedSourcePath = $this->normalizePath($sourcePath);

        if (isset($this->cache[$normalizedSourcePath])) {
            return $this->cache[$normalizedSourcePath];
        }

        if (!\file_exists($normalizedSourcePath)) {
            throw new RuntimeException(\sprintf('Asset source path "%s" does not exist.', $normalizedSourcePath));
        }

        $context = \hash_init('sha256');
        \hash_update($context, $this->logicalPath($normalizedSourcePath) . "\0");

        if (\is_file($normalizedSourcePath)) {
            $this->hashFile($context, $normalizedSourcePath, \basename($normalizedSourcePath));
        } else {
            $this->hashDirectory($context, $normalizedSourcePath);
        }

        $hash = \substr(\hash_final($context), 0, $this->length);
        $this->cache[$normalizedSourcePath] = $hash;

        return $hash;
    }

    /** @param resource $context */
    private function hashDirectory($context, string $directory): void
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $directory,
                FilesystemIterator::SKIP_DOTS | FilesystemIterator::CURRENT_AS_FILEINFO,
            ),
            RecursiveIteratorIterator::LEAVES_ONLY,
            RecursiveIteratorIterator::CATCH_GET_CHILD,
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->isLink()) {
                continue;
            }

            $absolutePath = $this->normalizePath($file->getPathname());
            $relativePath = \ltrim(\substr($absolutePath, \strlen($directory)), '/');
            $files[$relativePath] = $absolutePath;
        }

        \ksort($files, SORT_STRING);

        foreach ($files as $relativePath => $absolutePath) {
            $this->hashFile($context, $absolutePath, $relativePath);
        }
    }

    /** @param resource $context */
    private function hashFile($context, string $absolutePath, string $relativePath): void
    {
        \hash_update($context, "file\0" . $relativePath . "\0");

        $stream = @\fopen($absolutePath, 'rb');

        if ($stream === false) {
            throw new RuntimeException(\sprintf('Unable to read asset file "%s".', $absolutePath));
        }

        try {
            if (\hash_update_stream($context, $stream) === false) {
                throw new RuntimeException(\sprintf('Unable to hash asset file "%s".', $absolutePath));
            }
        } finally {
            \fclose($stream);
        }

        \hash_update($context, "\0");
    }

    private function logicalPath(string $sourcePath): string
    {
        foreach ($this->roots as $name => $rootPath) {
            if ($sourcePath === $rootPath) {
                return $name;
            }

            if (\str_starts_with($sourcePath, $rootPath . '/')) {
                return $name . '/' . \substr($sourcePath, \strlen($rootPath) + 1);
            }
        }

        return $sourcePath;
    }

    private function normalizePath(string $path): string
    {
        return \rtrim(\str_replace('\\', '/', $path), '/');
    }
}
