<?php

declare(strict_types=1);

namespace SolasWyrd\Yii2StaticAssets\Contract;

use SolasWyrd\Yii2StaticAssets\Publication\AssetBundlePublicationResult;
use yii\web\AssetBundle;

interface AssetBundlePublisherInterface
{
    /**
     * @param list<class-string<AssetBundle>> $assetBundleClasses
     */
    public function publish(array $assetBundleClasses): AssetBundlePublicationResult;
}
