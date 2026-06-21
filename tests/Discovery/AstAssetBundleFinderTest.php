<?php

declare(strict_types=1);

namespace SolasWyrd\Yii2StaticAssets\Tests\Discovery;

use PHPUnit\Framework\TestCase;
use SolasWyrd\Yii2StaticAssets\Configuration\StaticAssetsConfiguration;
use SolasWyrd\Yii2StaticAssets\Discovery\AstAssetBundleFinder;
use SolasWyrd\Yii2StaticAssets\Discovery\PhpClassExtractor;
use SolasWyrd\Yii2StaticAssets\Discovery\PhpFileCollector;
use SolasWyrd\Yii2StaticAssets\Tests\Fixture\DirectAsset;
use SolasWyrd\Yii2StaticAssets\Tests\Fixture\IndirectAsset;
use SolasWyrd\Yii2StaticAssets\Tests\Support\RecordingProgressReporter;

final class AstAssetBundleFinderTest extends TestCase
{
    public function testFindsDirectAndIndirectConcreteBundlesOnly(): void
    {
        $fixturePath = \dirname(__DIR__) . '/Fixture';
        $configuration = new StaticAssetsConfiguration(
            targetPath: \sys_get_temp_dir() . '/assets/builds',
            allowedBuildRoot: \sys_get_temp_dir(),
            baseUrl: '/assets/builds',
            scanPaths: [$fixturePath],
            hashRoots: ['fixtures' => $fixturePath],
        );
        $reporter = new RecordingProgressReporter();
        $finder = new AstAssetBundleFinder(
            new PhpFileCollector($configuration),
            new PhpClassExtractor(),
            $reporter,
        );

        $result = $finder->find();

        self::assertSame([DirectAsset::class, IndirectAsset::class], $result->bundleClasses);
        self::assertSame(4, $result->scannedFileCount);
        self::assertCount(2, $result->bundleFiles);
        self::assertSame([['label' => 'Scanning PHP files', 'total' => 4]], $reporter->starts);
        self::assertCount(4, $reporter->advances);
        self::assertStringContainsString('2 AssetBundle', $reporter->finishes[0]);
    }
}
