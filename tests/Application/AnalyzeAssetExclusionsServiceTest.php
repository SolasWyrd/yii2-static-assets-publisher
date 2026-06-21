<?php

declare(strict_types=1);

namespace SolasWyrd\Yii2StaticAssets\Tests\Application;

use SolasWyrd\Yii2StaticAssets\Application\AnalyzeAssetExclusionsService;
use SolasWyrd\Yii2StaticAssets\Configuration\StaticAssetsConfiguration;
use SolasWyrd\Yii2StaticAssets\Contract\AssetBundleFinderInterface;
use SolasWyrd\Yii2StaticAssets\Discovery\AssetBundleDiscoveryResult;
use SolasWyrd\Yii2StaticAssets\Discovery\ExcludedPathMatcher;
use SolasWyrd\Yii2StaticAssets\Tests\Support\TemporaryDirectoryTestCase;

final class AnalyzeAssetExclusionsServiceTest extends TemporaryDirectoryTestCase
{
    public function testCountsEveryConfiguredPatternAndReturnsAllSuggestions(): void
    {
        $this->createFile('vendor/acme/a/A.php', '<?php');
        $this->createFile('vendor/acme/b/B.php', '<?php');
        $this->createFile('runtime/Hidden.php', '<?php');
        $configuration = new StaticAssetsConfiguration(
            projectRoot: $this->temporaryDirectory,
            targetPath: $this->temporaryDirectory . '/builds',
            allowedBuildRoot: $this->temporaryDirectory,
            baseUrl: '/assets',
            scanPaths: [$this->temporaryDirectory],
            excludedPatterns: ['runtime/*', 'runtime/Hidden.php'],
            hashRoots: ['app' => $this->temporaryDirectory],
            suggestionMinimumPhpFiles: 1,
        );
        $finder = new class implements AssetBundleFinderInterface {
            public function find(): AssetBundleDiscoveryResult
            {
                return new AssetBundleDiscoveryResult([], [], 2);
            }
        };

        $result = (new AnalyzeAssetExclusionsService($configuration, $finder, new ExcludedPathMatcher($configuration)))->analyze();

        self::assertSame(3, $result->totalPhpFiles);
        self::assertSame(1, $result->excludedPhpFiles);
        self::assertSame(1, $result->configuredPatternMatches['runtime/*']);
        self::assertSame(1, $result->configuredPatternMatches['runtime/Hidden.php']);
        self::assertNotEmpty($result->suggestions);
    }
}
