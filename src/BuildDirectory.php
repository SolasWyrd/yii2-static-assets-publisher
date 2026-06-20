<?php

declare(strict_types=1);

namespace SolasWyrd\Yii2StaticAssets;

use SolasWyrd\Yii2StaticAssets\Exception\AssetPublicationException;
use SolasWyrd\Yii2StaticAssets\Exception\UnsafeBuildPathException;
use yii\helpers\FileHelper;

final class BuildDirectory
{
    public function recreate(string $targetPath): string
    {
        $normalizedPath = $this->validate($targetPath);

        if (\is_dir($normalizedPath)) {
            FileHelper::removeDirectory($normalizedPath);
        } elseif (\file_exists($normalizedPath)) {
            throw new AssetPublicationException(
                \sprintf('Target path "%s" exists and is not a directory.', $normalizedPath),
            );
        }

        if (!FileHelper::createDirectory($normalizedPath)) {
            throw new AssetPublicationException(
                \sprintf('Unable to create asset build directory "%s".', $normalizedPath),
            );
        }

        return $normalizedPath;
    }

    private function validate(string $targetPath): string
    {
        $normalizedPath = \rtrim(
            FileHelper::normalizePath($targetPath),
            DIRECTORY_SEPARATOR,
        );

        if ($normalizedPath === '' || $normalizedPath === DIRECTORY_SEPARATOR) {
            throw new UnsafeBuildPathException('Unsafe empty or root build path.');
        }

        if (\preg_match('/^[A-Za-z]:$/', $normalizedPath) === 1) {
            throw new UnsafeBuildPathException('Filesystem root cannot be used as build path.');
        }

        if (\basename($normalizedPath) !== 'builds') {
            throw new UnsafeBuildPathException(
                \sprintf('Build directory must be named "builds", got "%s".', $normalizedPath),
            );
        }

        if (\basename(\dirname($normalizedPath)) !== 'assets') {
            throw new UnsafeBuildPathException(
                \sprintf('Build directory must be located inside an "assets" directory: "%s".', $normalizedPath),
            );
        }

        return $normalizedPath;
    }
}
