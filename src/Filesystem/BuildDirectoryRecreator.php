<?php

declare(strict_types=1);

namespace SolasWyrd\Yii2StaticAssets\Filesystem;

use SolasWyrd\Yii2StaticAssets\Configuration\StaticAssetsConfiguration;
use SolasWyrd\Yii2StaticAssets\Exception\AssetPublicationException;
use SolasWyrd\Yii2StaticAssets\Exception\UnsafeBuildPathException;
use yii\helpers\FileHelper;

/** @internal */
final readonly class BuildDirectoryRecreator
{
    public function __construct(
        private StaticAssetsConfiguration $configuration,
    ) {}

    public function recreate(): string
    {
        $targetPath = $this->normalizePath($this->configuration->targetPath);
        $allowedRoot = $this->resolveExistingDirectory(
            $this->configuration->allowedBuildRoot,
        );
        $resolvedTargetPath = $this->resolvePotentialPath($targetPath);

        if (
            $resolvedTargetPath === $allowedRoot
            || !$this->isInsideDirectory($resolvedTargetPath, $allowedRoot)
        ) {
            throw new UnsafeBuildPathException(
                \sprintf(
                    'Build path "%s" is outside allowed root "%s".',
                    $targetPath,
                    $allowedRoot,
                ),
            );
        }

        if (\file_exists($targetPath) && !\is_dir($targetPath)) {
            throw new AssetPublicationException(
                \sprintf('Build path "%s" exists and is not a directory.', $targetPath),
            );
        }

        if (\is_dir($targetPath)) {
            FileHelper::removeDirectory($targetPath);
        }

        if (!FileHelper::createDirectory($targetPath)) {
            throw new AssetPublicationException(
                \sprintf('Unable to create build directory "%s".', $targetPath),
            );
        }

        $createdTargetPath = \realpath($targetPath);

        if (
            $createdTargetPath === false
            || !$this->isInsideDirectory($createdTargetPath, $allowedRoot)
        ) {
            FileHelper::removeDirectory($targetPath);

            throw new UnsafeBuildPathException(
                \sprintf(
                    'Created build path "%s" resolves outside allowed root "%s".',
                    $targetPath,
                    $allowedRoot,
                ),
            );
        }

        return $targetPath;
    }

    private function resolvePotentialPath(string $path): string
    {
        $existingPath = $path;
        $missingSegments = [];

        while (!\file_exists($existingPath) && !\is_link($existingPath)) {
            $parentPath = \dirname($existingPath);

            if ($parentPath === $existingPath) {
                throw new UnsafeBuildPathException(
                    \sprintf('Unable to resolve build path "%s".', $path),
                );
            }

            \array_unshift($missingSegments, \basename($existingPath));
            $existingPath = $parentPath;
        }

        $resolvedExistingPath = \realpath($existingPath);

        if ($resolvedExistingPath === false) {
            throw new UnsafeBuildPathException(
                \sprintf('Unable to resolve build path "%s".', $path),
            );
        }

        $resolvedPath = $resolvedExistingPath;

        foreach ($missingSegments as $segment) {
            $resolvedPath .= DIRECTORY_SEPARATOR . $segment;
        }

        return $this->normalizePath($resolvedPath);
    }

    private function resolveExistingDirectory(string $path): string
    {
        $resolvedPath = \realpath($path);

        if ($resolvedPath === false || !\is_dir($resolvedPath)) {
            throw new UnsafeBuildPathException(
                \sprintf(
                    'Allowed build root "%s" does not exist or is not a directory.',
                    $path,
                ),
            );
        }

        return $this->normalizePath($resolvedPath);
    }

    private function isInsideDirectory(string $path, string $directory): bool
    {
        return \str_starts_with(
            $path . DIRECTORY_SEPARATOR,
            $directory . DIRECTORY_SEPARATOR,
        );
    }

    private function normalizePath(string $path): string
    {
        return \rtrim(
            FileHelper::normalizePath($path),
            DIRECTORY_SEPARATOR,
        );
    }
}
