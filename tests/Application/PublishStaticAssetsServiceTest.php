<?php

declare(strict_types=1);

namespace SolasWyrd\Yii2StaticAssets\Tests\Application;

use PHPUnit\Framework\TestCase;
use SolasWyrd\Yii2StaticAssets\Application\PublishStaticAssetsService;
use SolasWyrd\Yii2StaticAssets\Contract\AssetBundleFinderInterface;
use SolasWyrd\Yii2StaticAssets\Contract\AssetBundlePublisherInterface;
use SolasWyrd\Yii2StaticAssets\Contract\AssetManifestWriterInterface;
use SolasWyrd\Yii2StaticAssets\Discovery\AssetBundleDiscoveryResult;
use SolasWyrd\Yii2StaticAssets\Exception\AssetPublicationException;
use SolasWyrd\Yii2StaticAssets\Publication\AssetBundlePublicationResult;
use SolasWyrd\Yii2StaticAssets\Tests\Fixture\DirectAsset;

final class PublishStaticAssetsServiceTest extends TestCase
{
    public function testCoordinatesFinderPublisherAndManifestWriter(): void
    {
        $finder = new class implements AssetBundleFinderInterface {
            public function find(): AssetBundleDiscoveryResult
            {
                return new AssetBundleDiscoveryResult([DirectAsset::class], ['/fixture.php'], 7);
            }
        };
        $publisher = new class implements AssetBundlePublisherInterface {
            /** @var list<class-string<\yii\web\AssetBundle>> */
            public array $receivedClasses = [];

            public function publish(array $assetBundleClasses): AssetBundlePublicationResult
            {
                $this->receivedClasses = $assetBundleClasses;

                return new AssetBundlePublicationResult('/builds', 1, ['app/assets' => 'hash']);
            }
        };
        $writer = new class implements AssetManifestWriterInterface {
            /** @var array<string, string> */
            public array $entries = [];

            public function write(array $entries): void
            {
                $this->entries = $entries;
            }
        };

        $result = (new PublishStaticAssetsService($finder, $publisher, $writer))->publish();

        self::assertSame([DirectAsset::class], $publisher->receivedClasses);
        self::assertSame(['app/assets' => 'hash'], $writer->entries);
        self::assertSame('/builds', $result->targetPath);
        self::assertSame(7, $result->scannedFileCount);
        self::assertSame(1, $result->publishedBundleCount);
    }

    public function testFailsWhenNoAssetBundlesWereFound(): void
    {
        $finder = new class implements AssetBundleFinderInterface {
            public function find(): AssetBundleDiscoveryResult
            {
                return new AssetBundleDiscoveryResult([], [], 0);
            }
        };
        $publisher = new class implements AssetBundlePublisherInterface {
            public function publish(array $assetBundleClasses): AssetBundlePublicationResult
            {
                throw new \LogicException('Publisher must not be called.');
            }
        };
        $writer = new class implements AssetManifestWriterInterface {
            public function write(array $entries): void
            {
                throw new \LogicException('Writer must not be called.');
            }
        };

        $this->expectException(AssetPublicationException::class);
        $this->expectExceptionMessage('No Yii2 AssetBundle');

        (new PublishStaticAssetsService($finder, $publisher, $writer))->publish();
    }
}
