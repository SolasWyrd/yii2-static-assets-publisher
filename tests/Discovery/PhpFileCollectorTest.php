<?php

declare(strict_types=1);

namespace SolasWyrd\Yii2StaticAssets\Tests\Discovery;

use SolasWyrd\Yii2StaticAssets\Configuration\StaticAssetsConfiguration;
use SolasWyrd\Yii2StaticAssets\Discovery\ExcludedPathMatcher;
use SolasWyrd\Yii2StaticAssets\Discovery\PhpFileCollector;
use SolasWyrd\Yii2StaticAssets\Tests\Support\TemporaryDirectoryTestCase;

final class PhpFileCollectorTest extends TemporaryDirectoryTestCase
{
    public function testCollectsUniqueSortedPhpFilesAndPrunesExcludedDirectories(): void
    {
        $first = $this->createFile('src/Z.php', '<?php');
        $second = $this->createFile('src/A.php', '<?php');
        $this->createFile('src/readme.txt', 'text');
        $this->createFile('tests/Hidden.php', '<?php');
        $configuration = new StaticAssetsConfiguration(
            projectRoot: $this->temporaryDirectory,
            targetPath: $this->temporaryDirectory . '/build',
            allowedBuildRoot: $this->temporaryDirectory,
            baseUrl: '/assets',
            scanPaths: [$this->temporaryDirectory, $this->temporaryDirectory . '/src'],
            excludedPatterns: ['tests/*'],
            hashRoots: ['app' => $this->temporaryDirectory],
        );

        self::assertSame(
            [
                \str_replace('\\', '/', $second),
                \str_replace('\\', '/', $first),
            ],
            (new PhpFileCollector($configuration, new ExcludedPathMatcher($configuration)))->collect(),
        );
    }

    public function testSkipsMissingScanPath(): void
    {
        $configuration = new StaticAssetsConfiguration(
            projectRoot: $this->temporaryDirectory,
            targetPath: $this->temporaryDirectory . '/build',
            allowedBuildRoot: $this->temporaryDirectory,
            baseUrl: '/assets',
            scanPaths: [$this->temporaryDirectory . '/missing'],
            excludedPatterns: [],
            hashRoots: ['app' => $this->temporaryDirectory],
        );

        self::assertSame([], (new PhpFileCollector($configuration, new ExcludedPathMatcher($configuration)))->collect());
    }
}
