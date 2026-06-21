<?php

declare(strict_types=1);

namespace SolasWyrd\Yii2StaticAssets\Publication;

final readonly class AssetBundlePublicationResult
{
    /** @param array<string,string> $manifestEntries */
    public function __construct(
        public string $targetPath,
        public int $publishedBundleCount,
        public array $manifestEntries,
    ) {}
}
