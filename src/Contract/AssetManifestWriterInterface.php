<?php

declare(strict_types=1);

namespace SolasWyrd\Yii2StaticAssets\Contract;

interface AssetManifestWriterInterface
{
    /** @param array<string,string> $entries */
    public function write(array $entries): void;
}
