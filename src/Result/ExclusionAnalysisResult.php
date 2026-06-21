<?php

declare(strict_types=1);

namespace SolasWyrd\Yii2StaticAssets\Result;

final readonly class ExclusionAnalysisResult
{
    /**
     * @param array<string,int>       $configuredPatternMatches
     * @param list<ExclusionSuggestion> $suggestions
     */
    public function __construct(
        public int $totalPhpFiles,
        public int $excludedPhpFiles,
        public int $analyzedPhpFiles,
        public int $assetBundleCount,
        public array $configuredPatternMatches,
        public array $suggestions,
        public float $durationSeconds,
    ) {}
}
