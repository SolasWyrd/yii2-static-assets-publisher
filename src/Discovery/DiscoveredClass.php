<?php

declare(strict_types=1);

namespace SolasWyrd\Yii2StaticAssets\Discovery;

/** @internal */
final readonly class DiscoveredClass
{
    public function __construct(
        public string $className,
        public ?string $parentClassName,
        public bool $abstract,
        public string $filePath,
    ) {}
}
