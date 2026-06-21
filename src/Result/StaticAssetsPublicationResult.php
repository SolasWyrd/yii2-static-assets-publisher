<?php

declare(strict_types=1);

namespace SolasWyrd\Yii2StaticAssets\Result;

final readonly class StaticAssetsPublicationResult
{
    public function __construct(
        public string $targetPath,
        public int $scannedFileCount,
        public int $publishedBundleCount,
        public float $durationSeconds,
    ) {}
}
