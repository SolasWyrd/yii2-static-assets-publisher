<?php

declare(strict_types=1);

namespace SolasWyrd\Yii2StaticAssets\Configuration;

use InvalidArgumentException;

final readonly class StaticAssetsConfiguration
{
    /**
     * @param list<string> $scanPaths
     * @param list<string> $excludedPatterns
     * @param array<string,string> $hashRoots
     */
    public function __construct(
        public string $projectRoot,
        public string $targetPath,
        public string $allowedBuildRoot,
        public string $baseUrl,
        public array $scanPaths,
        public array $excludedPatterns,
        public array $hashRoots,
        public string $manifestFileName = 'static-assets-manifest.json',
        public int $suggestionMinimumPhpFiles = 50,
    ) {
        if ($projectRoot === '') {
            throw new InvalidArgumentException('Project root is required.');
        }

        if ($targetPath === '') {
            throw new InvalidArgumentException('Target path is required.');
        }

        if ($allowedBuildRoot === '') {
            throw new InvalidArgumentException('Allowed build root is required.');
        }

        if ($baseUrl === '') {
            throw new InvalidArgumentException('Base URL is required.');
        }

        if ($scanPaths === []) {
            throw new InvalidArgumentException('At least one scan path is required.');
        }

        if ($hashRoots === []) {
            throw new InvalidArgumentException('At least one hash root is required.');
        }

        if ($manifestFileName === '' || \basename($manifestFileName) !== $manifestFileName) {
            throw new InvalidArgumentException('Manifest file name must be a plain file name.');
        }

        if ($suggestionMinimumPhpFiles < 1) {
            throw new InvalidArgumentException('Suggestion minimum PHP files must be positive.');
        }
    }

    public function manifestPath(): string
    {
        return \rtrim($this->targetPath, '/\\')
            . DIRECTORY_SEPARATOR
            . $this->manifestFileName;
    }
}
