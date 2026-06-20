<?php

declare(strict_types=1);

namespace SolasWyrd\Yii2StaticAssets\Discovery;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final readonly class RecursivePhpFileProvider implements PhpFileProvider
{
    /**
     * @param list<string> $roots
     * @param list<string> $excludedPatterns
     */
    public function __construct(
        private array $roots,
        private array $excludedPatterns = [],
    ) {
    }

    public function files(): iterable
    {
        $seen = [];

        foreach ($this->roots as $root) {
            if (!\is_dir($root)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $root,
                    RecursiveDirectoryIterator::SKIP_DOTS,
                ),
                RecursiveIteratorIterator::LEAVES_ONLY,
                RecursiveIteratorIterator::CATCH_GET_CHILD,
            );

            /** @var SplFileInfo $file */
            foreach ($iterator as $file) {
                if (!$file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }

                $path = $this->normalizePath($file->getPathname());

                if ($this->isExcluded($path) || isset($seen[$path])) {
                    continue;
                }

                $seen[$path] = true;
                yield $path;
            }
        }
    }

    private function isExcluded(string $path): bool
    {
        foreach ($this->excludedPatterns as $pattern) {
            if (\fnmatch($pattern, $path)) {
                return true;
            }
        }

        return false;
    }

    private function normalizePath(string $path): string
    {
        return \str_replace('\\', '/', $path);
    }
}
