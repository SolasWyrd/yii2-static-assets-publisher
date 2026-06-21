<?php

declare(strict_types=1);

namespace SolasWyrd\Yii2StaticAssets\Discovery;

use InvalidArgumentException;
use SolasWyrd\Yii2StaticAssets\Configuration\StaticAssetsConfiguration;

/** @internal */
final readonly class ExcludedPathMatcher
{
    public function __construct(
        private StaticAssetsConfiguration $configuration,
    ) {}

    public function matches(string $absolutePath): bool
    {
        foreach ($this->configuration->excludedPatterns as $pattern) {
            if ($this->matchesPattern($absolutePath, $pattern)) {
                return true;
            }
        }

        return false;
    }

    public function matchesPattern(
        string $absolutePath,
        string $pattern,
    ): bool {
        $relativePath = $this->relativePath($absolutePath);

        if (\str_ends_with($pattern, '/*')) {
            $directoryPattern = \substr($pattern, 0, -2);

            if ($relativePath === $directoryPattern) {
                return true;
            }
        }

        return \fnmatch($pattern, $relativePath);
    }

    public function patternForDirectory(string $absoluteDirectory): string
    {
        return \rtrim(
            $this->relativePath($absoluteDirectory),
            '/',
        ) . '/*';
    }

    public function relativePath(string $absolutePath): string
    {
        $absolutePath = $this->normalize($absolutePath);
        $projectRoot = \rtrim(
            $this->normalize($this->configuration->projectRoot),
            '/',
        );

        if (
            $absolutePath !== $projectRoot
            && !\str_starts_with($absolutePath, $projectRoot . '/')
        ) {
            throw new InvalidArgumentException(
                \sprintf(
                    'Path "%s" is outside project root "%s".',
                    $absolutePath,
                    $projectRoot,
                ),
            );
        }

        return \ltrim(
            \substr($absolutePath, \strlen($projectRoot)),
            '/',
        );
    }

    private function normalize(string $path): string
    {
        return \str_replace('\\', '/', $path);
    }
}
