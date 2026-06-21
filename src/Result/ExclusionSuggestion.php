<?php

declare(strict_types=1);

namespace SolasWyrd\Yii2StaticAssets\Result;

final readonly class ExclusionSuggestion
{
    public function __construct(
        public string $pattern,
        public string $directory,
        public int $phpFileCount,
        public float $percentage,
        public bool $vendorSafe,
    ) {}
}
