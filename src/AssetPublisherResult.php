<?php

declare(strict_types=1);

namespace SolasWyrd\Yii2StaticAssets;

use yii\web\AssetBundle;

final readonly class AssetPublisherResult
{
    /**
     * @param list<class-string<AssetBundle>> $publishedBundleClasses
     */
    public function __construct(
        public string $targetPath,
        public array $publishedBundleClasses,
    ) {
    }

    public function publishedBundleCount(): int
    {
        return \count($this->publishedBundleClasses);
    }
}
