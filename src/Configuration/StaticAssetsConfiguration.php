<?php

declare(strict_types=1);

namespace SolasWyrd\Yii2StaticAssets\Configuration;

use InvalidArgumentException;

final readonly class StaticAssetsConfiguration
{
    /**
     * @param list<string>          $scanPaths
     * @param array<string, string> $hashRoots
     */
    public function __construct(
        public string $targetPath,
        public string $allowedBuildRoot,
        public string $baseUrl,
        public array $scanPaths,
        public array $hashRoots,
        public string $manifestFileName = 'static-assets-manifest.json',
    ) {
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
    }

    public function manifestPath(): string
    {
        return \rtrim($this->targetPath, '/\\')
            . DIRECTORY_SEPARATOR
            . $this->manifestFileName;
    }
}
