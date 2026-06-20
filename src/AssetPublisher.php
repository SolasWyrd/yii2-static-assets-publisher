<?php

declare(strict_types=1);

namespace SolasWyrd\Yii2StaticAssets;

use SolasWyrd\Yii2StaticAssets\Discovery\AssetBundleDiscoverer;
use SolasWyrd\Yii2StaticAssets\Exception\AssetPublicationException;
use Closure;
use Throwable;
use Yii;
use yii\web\AssetManager;

final readonly class AssetPublisher
{
    /** @param Closure(string): string $hashCallback */
    public function __construct(
        private AssetBundleDiscoverer $discoverer,
        private BuildDirectory $buildDirectory,
        private Closure $hashCallback,
    ) {
    }

    public function publish(AssetPublisherRequest $request): AssetPublisherResult
    {
        try {
            $bundleClasses = $this->discoverer->discover();

            if ($bundleClasses === []) {
                throw new AssetPublicationException('No Yii2 AssetBundle classes were discovered.');
            }

            $targetPath = $this->buildDirectory->recreate($request->targetPath);
            $assetManager = $this->createAssetManager($targetPath, $request->baseUrl);

            foreach ($bundleClasses as $bundleClass) {
                $assetManager->getBundle($bundleClass, true);
            }

            return new AssetPublisherResult($targetPath, $bundleClasses);
        } catch (AssetPublicationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new AssetPublicationException(
                'Static asset publication failed.',
                previous: $exception,
            );
        }
    }

    private function createAssetManager(string $basePath, string $baseUrl): AssetManager
    {
        /** @var AssetManager $assetManager */
        $assetManager = Yii::createObject([
            'class' => AssetManager::class,
            'basePath' => $basePath,
            'baseUrl' => $baseUrl,
            'appendTimestamp' => false,
            'linkAssets' => false,
            'forceCopy' => true,
            'hashCallback' => $this->hashCallback,
        ]);

        return $assetManager;
    }
}
