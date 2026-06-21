<?php

declare(strict_types=1);

namespace SolasWyrd\Yii2StaticAssets\Contract;

use SolasWyrd\Yii2StaticAssets\Discovery\AssetBundleDiscoveryResult;

interface AssetBundleFinderInterface
{
    public function find(): AssetBundleDiscoveryResult;
}
