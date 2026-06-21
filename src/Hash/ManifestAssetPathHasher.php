<?php

declare(strict_types=1);

namespace SolasWyrd\Yii2StaticAssets\Hash;

use RuntimeException;
use SolasWyrd\Yii2StaticAssets\Contract\AssetManifestReaderInterface;
use SolasWyrd\Yii2StaticAssets\Contract\AssetSourceHasherInterface;

final readonly class ManifestAssetPathHasher
{
    public function __construct(
        private AssetManifestReaderInterface $manifestReader,
        private AssetSourceHasherInterface $sourceHasher,
    ) {}

    public function __invoke(string $sourcePath): string
    {
        $sourceKey = $this->sourceHasher->sourceKey($sourcePath);
        $manifest = $this->manifestReader->read();

        if (!isset($manifest[$sourceKey])) {
            throw new RuntimeException(
                \sprintf('Asset path "%s" is missing from manifest.', $sourceKey),
            );
        }

        return $manifest[$sourceKey];
    }
}
