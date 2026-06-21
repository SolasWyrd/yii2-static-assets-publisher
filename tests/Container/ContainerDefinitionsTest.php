<?php

declare(strict_types=1);

namespace SolasWyrd\Yii2StaticAssets\Tests\Container;

use PHPUnit\Framework\TestCase;
use SolasWyrd\Yii2StaticAssets\Application\PublishStaticAssetsService;
use SolasWyrd\Yii2StaticAssets\Configuration\StaticAssetsConfiguration;
use SolasWyrd\Yii2StaticAssets\Contract\AssetBundleFinderInterface;
use SolasWyrd\Yii2StaticAssets\Contract\AssetBundlePublisherInterface;
use SolasWyrd\Yii2StaticAssets\Contract\AssetManifestReaderInterface;
use SolasWyrd\Yii2StaticAssets\Contract\AssetManifestWriterInterface;
use SolasWyrd\Yii2StaticAssets\Contract\AssetSourceHasherInterface;
use SolasWyrd\Yii2StaticAssets\Contract\ProgressReporterInterface;
use SolasWyrd\Yii2StaticAssets\Discovery\AstAssetBundleFinder;
use SolasWyrd\Yii2StaticAssets\Hash\ContentAssetSourceHasher;
use SolasWyrd\Yii2StaticAssets\Manifest\JsonFileAssetManifestReader;
use SolasWyrd\Yii2StaticAssets\Manifest\JsonFileAssetManifestWriter;
use SolasWyrd\Yii2StaticAssets\Publication\YiiAssetBundlePublisher;
use SolasWyrd\Yii2StaticAssets\Reporting\NullProgressReporter;
use yii\di\Container;

final class ContainerDefinitionsTest extends TestCase
{
    public function testContainerBuildsServicesFromDocumentedDefinitions(): void
    {
        $container = new Container();
        $configuration = new StaticAssetsConfiguration(
            targetPath: \sys_get_temp_dir() . '/assets/builds',
            allowedBuildRoot: \sys_get_temp_dir(),
            baseUrl: '/assets/builds',
            scanPaths: [\sys_get_temp_dir()],
            hashRoots: ['app' => \sys_get_temp_dir()],
        );
        $container->setSingleton(StaticAssetsConfiguration::class, $configuration);
        $container->set(AssetBundleFinderInterface::class, AstAssetBundleFinder::class);
        $container->set(AssetBundlePublisherInterface::class, YiiAssetBundlePublisher::class);
        $container->set(AssetSourceHasherInterface::class, ContentAssetSourceHasher::class);
        $container->set(AssetManifestReaderInterface::class, JsonFileAssetManifestReader::class);
        $container->set(AssetManifestWriterInterface::class, JsonFileAssetManifestWriter::class);
        $container->set(ProgressReporterInterface::class, NullProgressReporter::class);

        self::assertInstanceOf(PublishStaticAssetsService::class, $container->get(PublishStaticAssetsService::class));
        self::assertInstanceOf(AssetManifestReaderInterface::class, $container->get(AssetManifestReaderInterface::class));
    }
}
