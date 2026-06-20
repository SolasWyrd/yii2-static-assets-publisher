<?php

declare(strict_types=1);

namespace SolasWyrd\Yii2StaticAssets\Discovery;

use SolasWyrd\Yii2StaticAssets\Exception\AssetDiscoveryException;

final readonly class ComposerClassMapFileProvider implements PhpFileProvider
{
    /**
     * @param list<string> $includedRoots
     * @param list<string> $excludedPatterns
     */
    public function __construct(
        private string $classMapFile,
        private array $includedRoots,
        private array $excludedPatterns = [],
    ) {
    }

    public function files(): iterable
    {
        if (!\is_file($this->classMapFile)) {
            throw new AssetDiscoveryException(
                \sprintf('Composer classmap file "%s" not found.', $this->classMapFile),
            );
        }

        $classMap = require $this->classMapFile;

        if (!\is_array($classMap)) {
            throw new AssetDiscoveryException(
                \sprintf('Composer classmap file "%s" returned invalid data.', $this->classMapFile),
            );
        }

        $seen = [];
        $roots = \array_map([$this, 'normalizePath'], $this->includedRoots);

        foreach ($classMap as $filePath) {
            if (!\is_string($filePath) || !\is_file($filePath)) {
                continue;
            }

            $normalizedPath = $this->normalizePath($filePath);

            if (!$this->isInsideIncludedRoots($normalizedPath, $roots)) {
                continue;
            }

            if ($this->isExcluded($normalizedPath)) {
                continue;
            }

            if (isset($seen[$normalizedPath])) {
                continue;
            }

            $seen[$normalizedPath] = true;
            yield $normalizedPath;
        }
    }

    /** @param list<string> $roots */
    private function isInsideIncludedRoots(string $path, array $roots): bool
    {
        foreach ($roots as $root) {
            if ($path === $root || \str_starts_with($path, $root . '/')) {
                return true;
            }
        }

        return false;
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
        return \rtrim(\str_replace('\\', '/', $path), '/');
    }
}
