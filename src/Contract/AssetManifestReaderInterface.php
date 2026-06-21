<?php

declare(strict_types=1);

namespace SolasWyrd\Yii2StaticAssets\Contract;

interface AssetManifestReaderInterface
{
    /** @return array<string,string> */
    public function read(): array;
}
