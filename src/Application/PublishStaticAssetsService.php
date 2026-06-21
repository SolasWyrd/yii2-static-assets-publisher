<?php

declare(strict_types=1);

namespace SolasWyrd\Yii2StaticAssets\Application;

use SolasWyrd\Yii2StaticAssets\Contract\AssetBundleFinderInterface;
use SolasWyrd\Yii2StaticAssets\Contract\AssetBundlePublisherInterface;
use SolasWyrd\Yii2StaticAssets\Contract\AssetManifestWriterInterface;
use SolasWyrd\Yii2StaticAssets\Exception\AssetPublicationException;
use SolasWyrd\Yii2StaticAssets\Result\StaticAssetsPublicationResult;
use Throwable;

final readonly class PublishStaticAssetsService
{
    public function __construct(
        private AssetBundleFinderInterface $assetBundleFinder,
        private AssetBundlePublisherInterface $assetBundlePublisher,
        private AssetManifestWriterInterface $manifestWriter,
    ) {}

    public function publish(): StaticAssetsPublicationResult
    {
        $startedAt = \microtime(true);

        try {
            $discoveryResult = $this->assetBundleFinder->find();

            if ($discoveryResult->bundleClasses === []) {
                throw new AssetPublicationException(
                    'No Yii2 AssetBundle classes were discovered.',
                );
            }

            $publicationResult = $this->assetBundlePublisher->publish(
                $discoveryResult->bundleClasses,
            );
            $this->manifestWriter->write($publicationResult->manifestEntries);

            return new StaticAssetsPublicationResult(
                targetPath: $publicationResult->targetPath,
                scannedFileCount: $discoveryResult->scannedFileCount,
                publishedBundleCount: $publicationResult->publishedBundleCount,
                durationSeconds: \microtime(true) - $startedAt,
            );
        } catch (AssetPublicationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new AssetPublicationException(
                'Static asset publication failed.',
                previous: $exception,
            );
        }
    }
}
