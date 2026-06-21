<?php

declare(strict_types=1);

namespace SolasWyrd\Yii2StaticAssets\Discovery;

use yii\web\AssetBundle;

final readonly class AssetBundleDiscoveryResult
{
    /**
     * @param list<class-string<AssetBundle>> $bundleClasses
     * @param list<string>                    $bundleFiles
     */
    public function __construct(
        public array $bundleClasses,
        public array $bundleFiles,
        public int $scannedFileCount,
    ) {}
}
