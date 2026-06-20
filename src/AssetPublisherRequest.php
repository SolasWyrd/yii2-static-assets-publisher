<?php

declare(strict_types=1);

namespace SolasWyrd\Yii2StaticAssets;

use InvalidArgumentException;

final readonly class AssetPublisherRequest
{
    public function __construct(
        public string $targetPath,
        public string $baseUrl,
    ) {
        if ($targetPath === '') {
            throw new InvalidArgumentException('Target path cannot be empty.');
        }

        if ($baseUrl === '' || !\str_starts_with($baseUrl, '/')) {
            throw new InvalidArgumentException('Base URL must be absolute and start with "/".');
        }
    }
}
