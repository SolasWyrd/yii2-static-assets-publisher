<?php

declare(strict_types=1);

namespace SolasWyrd\Yii2StaticAssets\Tests\Filesystem;

use SolasWyrd\Yii2StaticAssets\Configuration\StaticAssetsConfiguration;
use SolasWyrd\Yii2StaticAssets\Exception\AssetPublicationException;
use SolasWyrd\Yii2StaticAssets\Exception\UnsafeBuildPathException;
use SolasWyrd\Yii2StaticAssets\Filesystem\BuildDirectoryRecreator;
use SolasWyrd\Yii2StaticAssets\Tests\Support\TemporaryDirectoryTestCase;

final class BuildDirectoryRecreatorTest extends TemporaryDirectoryTestCase
{
    public function testRecreatesDirectoryInsideAllowedRoot(): void
    {
        $targetPath = $this->temporaryDirectory . '/assets/builds';
        $this->createFile('assets/builds/old.txt', 'old');

        $result = (new BuildDirectoryRecreator($this->configuration($targetPath)))->recreate();

        self::assertSame($targetPath, $result);
        self::assertDirectoryExists($targetPath);
        self::assertFileDoesNotExist($targetPath . '/old.txt');
    }

    public function testRejectsAllowedRootItself(): void
    {
        $this->expectException(UnsafeBuildPathException::class);

        (new BuildDirectoryRecreator($this->configuration($this->temporaryDirectory)))->recreate();
    }

    public function testRejectsPathOutsideAllowedRoot(): void
    {
        $outsidePath = \dirname($this->temporaryDirectory) . '/outside-' . \bin2hex(\random_bytes(4));

        $this->expectException(UnsafeBuildPathException::class);

        (new BuildDirectoryRecreator($this->configuration($outsidePath)))->recreate();
    }

    public function testRejectsFileAsTargetPath(): void
    {
        $targetPath = $this->createFile('build-file', 'content');

        $this->expectException(AssetPublicationException::class);
        $this->expectExceptionMessage('is not a directory');

        (new BuildDirectoryRecreator($this->configuration($targetPath)))->recreate();
    }

    public function testRejectsMissingAllowedRoot(): void
    {
        $configuration = new StaticAssetsConfiguration(
            targetPath: $this->temporaryDirectory . '/missing-root/builds',
            allowedBuildRoot: $this->temporaryDirectory . '/missing-root',
            baseUrl: '/assets',
            scanPaths: [$this->temporaryDirectory],
            hashRoots: ['app' => $this->temporaryDirectory],
        );

        $this->expectException(UnsafeBuildPathException::class);
        $this->expectExceptionMessage('does not exist');

        (new BuildDirectoryRecreator($configuration))->recreate();
    }

    public function testRejectsParentSymlinkEscapingAllowedRoot(): void
    {
        if (!\function_exists('symlink')) {
            self::markTestSkipped('Symlinks are unavailable.');
        }

        $outside = \dirname($this->temporaryDirectory) . '/outside-' . \bin2hex(\random_bytes(4));
        self::assertTrue(\mkdir($outside));
        $link = $this->temporaryDirectory . '/link';

        if (!@\symlink($outside, $link)) {
            self::markTestSkipped('Unable to create symlink.');
        }

        try {
            $this->expectException(UnsafeBuildPathException::class);

            (new BuildDirectoryRecreator($this->configuration($link . '/builds')))->recreate();
        } finally {
            @\unlink($link);
            @\rmdir($outside);
        }
    }

    private function configuration(string $targetPath): StaticAssetsConfiguration
    {
        return new StaticAssetsConfiguration(
            targetPath: $targetPath,
            allowedBuildRoot: $this->temporaryDirectory,
            baseUrl: '/assets',
            scanPaths: [$this->temporaryDirectory],
            hashRoots: ['app' => $this->temporaryDirectory],
        );
    }
}
