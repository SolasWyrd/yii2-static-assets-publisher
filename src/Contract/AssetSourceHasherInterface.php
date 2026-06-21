<?php

declare(strict_types=1);

namespace SolasWyrd\Yii2StaticAssets\Contract;

interface AssetSourceHasherInterface
{
    public function sourceKey(string $sourcePath): string;

    public function hash(string $sourcePath): string;
}
