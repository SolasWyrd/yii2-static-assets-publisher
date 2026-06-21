<?php

declare(strict_types=1);

namespace SolasWyrd\Yii2StaticAssets\Publication;

use SolasWyrd\Yii2StaticAssets\Configuration\StaticAssetsConfiguration;
use SolasWyrd\Yii2StaticAssets\Contract\AssetBundlePublisherInterface;
use SolasWyrd\Yii2StaticAssets\Contract\AssetSourceHasherInterface;
use SolasWyrd\Yii2StaticAssets\Contract\ProgressReporterInterface;
use SolasWyrd\Yii2StaticAssets\Filesystem\BuildDirectoryRecreator;
use Yii;
use yii\web\AssetManager;

final readonly class YiiAssetBundlePublisher implements AssetBundlePublisherInterface
{
    public function __construct(
        private StaticAssetsConfiguration $configuration,
        private BuildDirectoryRecreator $buildDirectoryRecreator,
        private AssetSourceHasherInterface $sourceHasher,
        private ProgressReporterInterface $progressReporter,
    ) {}

    public function publish(array $assetBundleClasses): AssetBundlePublicationResult
    {
        $targetPath = $this->buildDirectoryRecreator->recreate();
        $manifestEntries = [];
        $sourceHasher = $this->sourceHasher;

        $hashCallback = static function (string $sourcePath) use (
            &$manifestEntries,
            $sourceHasher,
        ): string {
            $hash = $sourceHasher->hash($sourcePath);
            $manifestEntries[$sourceHasher->sourceKey($sourcePath)] = $hash;

            return $hash;
        };

        /** @var AssetManager $assetManager */
        $assetManager = Yii::createObject([
            'class' => AssetManager::class,
            'basePath' => $targetPath,
            'baseUrl' => $this->configuration->baseUrl,
            'appendTimestamp' => false,
            'linkAssets' => false,
            'forceCopy' => true,
            'hashCallback' => $hashCallback,
        ]);

        $totalBundles = \count($assetBundleClasses);
        $this->progressReporter->start('Publishing AssetBundle classes', $totalBundles);

        foreach ($assetBundleClasses as $index => $assetBundleClass) {
            $assetManager->getBundle($assetBundleClass, true);
            $this->progressReporter->advance($index + 1, $totalBundles);
        }

        \ksort($manifestEntries);
        $this->progressReporter->finish(
            \sprintf('Published %d AssetBundle classes.', $totalBundles),
        );

        return new AssetBundlePublicationResult(
            $targetPath,
            $totalBundles,
            $manifestEntries,
        );
    }
}
