<?php

declare(strict_types=1);

namespace SolasWyrd\Yii2StaticAssets\Tests\Discovery;

use SolasWyrd\Yii2StaticAssets\Configuration\StaticAssetsConfiguration;
use SolasWyrd\Yii2StaticAssets\Discovery\PhpFileCollector;
use SolasWyrd\Yii2StaticAssets\Tests\Support\TemporaryDirectoryTestCase;

final class PhpFileCollectorTest extends TemporaryDirectoryTestCase
{
    public function testCollectsUniqueSortedPhpFiles(): void
    {
        $first = $this->createFile('src/Z.php', '<?php');
        $second = $this->createFile('src/A.php', '<?php');
        $third = $this->createFile('tests/Fixture.php', '<?php');
        $this->createFile('src/readme.txt', 'text');
        $configuration = new StaticAssetsConfiguration(
            targetPath: $this->temporaryDirectory . '/build',
            allowedBuildRoot: $this->temporaryDirectory,
            baseUrl: '/assets',
            scanPaths: [$this->temporaryDirectory, $this->temporaryDirectory . '/src'],
            hashRoots: ['app' => $this->temporaryDirectory],
        );

        self::assertSame(
            [
                \str_replace('\\', '/', $second),
                \str_replace('\\', '/', $first),
                \str_replace('\\', '/', $third),
            ],
            (new PhpFileCollector($configuration))->collect(),
        );
    }

    public function testSkipsMissingScanPath(): void
    {
        $configuration = new StaticAssetsConfiguration(
            targetPath: $this->temporaryDirectory . '/build',
            allowedBuildRoot: $this->temporaryDirectory,
            baseUrl: '/assets',
            scanPaths: [$this->temporaryDirectory . '/missing'],
            hashRoots: ['app' => $this->temporaryDirectory],
        );

        self::assertSame([], (new PhpFileCollector($configuration))->collect());
    }
}
