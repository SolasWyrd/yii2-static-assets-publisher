<?php

declare(strict_types=1);

namespace SolasWyrd\Yii2StaticAssets\Application;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SolasWyrd\Yii2StaticAssets\Configuration\StaticAssetsConfiguration;
use SolasWyrd\Yii2StaticAssets\Contract\AssetBundleFinderInterface;
use SolasWyrd\Yii2StaticAssets\Discovery\ExcludedPathMatcher;
use SolasWyrd\Yii2StaticAssets\Result\ExclusionAnalysisResult;
use SolasWyrd\Yii2StaticAssets\Result\ExclusionSuggestion;
use SplFileInfo;

final readonly class AnalyzeAssetExclusionsService
{
    public function __construct(
        private StaticAssetsConfiguration $configuration,
        private AssetBundleFinderInterface $assetBundleFinder,
        private ExcludedPathMatcher $excludedPathMatcher,
    ) {}

    public function analyze(): ExclusionAnalysisResult
    {
        $startedAt = \microtime(true);
        $allPhpFiles = [];
        $configuredPatternMatches = \array_fill_keys(
            $this->configuration->excludedPatterns,
            0,
        );

        foreach ($this->configuration->scanPaths as $root) {
            if (!\is_dir($root)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $root,
                    RecursiveDirectoryIterator::SKIP_DOTS,
                ),
                RecursiveIteratorIterator::LEAVES_ONLY,
                RecursiveIteratorIterator::CATCH_GET_CHILD,
            );

            foreach ($iterator as $file) {
                if (
                    !$file instanceof SplFileInfo
                    || !$file->isFile()
                    || $file->getExtension() !== 'php'
                ) {
                    continue;
                }

                $filePath = $this->normalize($file->getPathname());
                $allPhpFiles[$filePath] = true;

                foreach ($configuredPatternMatches as $pattern => $count) {
                    if (
                        $this->excludedPathMatcher->matchesPattern(
                            $filePath,
                            $pattern,
                        )
                    ) {
                        $configuredPatternMatches[$pattern]++;
                    }
                }
            }
        }

        $analyzedFiles = [];

        foreach (\array_keys($allPhpFiles) as $filePath) {
            if ($this->excludedPathMatcher->matches($filePath)) {
                continue;
            }

            $analyzedFiles[] = $filePath;
        }

        \sort($analyzedFiles);

        $discoveryResult = $this->assetBundleFinder->find();
        $assetDirectories = $this->collectAssetDirectories(
            $discoveryResult->bundleFiles,
        );
        $directoryPhpFileCounts = [];

        foreach ($analyzedFiles as $filePath) {
            foreach (
                $this->candidateDirectories(\dirname($filePath)) as $directory
            ) {
                $directoryPhpFileCounts[$directory] =
                    ($directoryPhpFileCounts[$directory] ?? 0) + 1;
            }
        }

        \arsort($directoryPhpFileCounts);

        $suggestions = [];
        $selectedDirectories = [];
        $analyzedFileCount = \count($analyzedFiles);

        foreach ($directoryPhpFileCounts as $directory => $phpFileCount) {
            if (
                $phpFileCount
                < $this->configuration->suggestionMinimumPhpFiles
            ) {
                continue;
            }

            if (isset($assetDirectories[$directory])) {
                continue;
            }

            if (
                $this->isCoveredBySelectedDirectory(
                    $directory,
                    $selectedDirectories,
                )
            ) {
                continue;
            }

            $relativeDirectory = $this->excludedPathMatcher->relativePath(
                $directory,
            );

            $suggestions[] = new ExclusionSuggestion(
                pattern: $this->excludedPathMatcher->patternForDirectory(
                    $directory,
                ),
                directory: $directory,
                phpFileCount: $phpFileCount,
                percentage: $analyzedFileCount > 0
                    ? ($phpFileCount * 100) / $analyzedFileCount
                    : 0.0,
                vendorSafe: \str_starts_with(
                    $relativeDirectory,
                    'vendor/',
                ),
            );

            $selectedDirectories[] = $directory;
        }

        return new ExclusionAnalysisResult(
            totalPhpFiles: \count($allPhpFiles),
            excludedPhpFiles: \count($allPhpFiles) - $analyzedFileCount,
            analyzedPhpFiles: $analyzedFileCount,
            assetBundleCount: \count($discoveryResult->bundleClasses),
            configuredPatternMatches: $configuredPatternMatches,
            suggestions: $suggestions,
            durationSeconds: \microtime(true) - $startedAt,
        );
    }

    /**
     * @param list<string> $bundleFiles
     *
     * @return array<string, true>
     */
    private function collectAssetDirectories(array $bundleFiles): array
    {
        $directories = [];

        foreach ($bundleFiles as $bundleFile) {
            $directory = \dirname($this->normalize($bundleFile));

            while (
                $directory !== '/'
                && $directory !== '.'
                && $directory !== ''
            ) {
                $directories[$directory] = true;

                $parentDirectory = \dirname($directory);

                if ($parentDirectory === $directory) {
                    break;
                }

                $directory = $parentDirectory;
            }
        }

        return $directories;
    }

    /**
     * @return list<string>
     */
    private function candidateDirectories(string $directory): array
    {
        $relativePath = $this->excludedPathMatcher->relativePath(
            $directory,
        );

        if ($relativePath === '') {
            return [];
        }

        $parts = \explode('/', $relativePath);
        $isVendorDirectory = $parts[0] === 'vendor';

        $minimumDepth = $isVendorDirectory ? 3 : 1;
        $maximumDepth = $isVendorDirectory ? 4 : 3;
        $partsCount = \count($parts);

        if ($partsCount < $minimumDepth) {
            return [];
        }

        $projectRoot = \rtrim(
            $this->normalize($this->configuration->projectRoot),
            '/',
        );
        $candidates = [];

        for (
            $depth = $minimumDepth;
            $depth <= \min($partsCount, $maximumDepth);
            $depth++
        ) {
            $candidates[] = $projectRoot
                . '/'
                . \implode(
                    '/',
                    \array_slice($parts, 0, $depth),
                );
        }

        return $candidates;
    }

    /**
     * @param list<string> $selectedDirectories
     */
    private function isCoveredBySelectedDirectory(
        string $directory,
        array $selectedDirectories,
    ): bool {
        foreach ($selectedDirectories as $selectedDirectory) {
            if (
                $directory === $selectedDirectory
                || \str_starts_with(
                    $directory . '/',
                    $selectedDirectory . '/',
                )
            ) {
                return true;
            }
        }

        return false;
    }

    private function normalize(string $path): string
    {
        return \str_replace('\\', '/', $path);
    }
}
