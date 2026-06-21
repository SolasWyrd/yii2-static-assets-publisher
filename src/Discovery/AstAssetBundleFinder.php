<?php

declare(strict_types=1);

namespace SolasWyrd\Yii2StaticAssets\Discovery;

use SolasWyrd\Yii2StaticAssets\Contract\AssetBundleFinderInterface;
use SolasWyrd\Yii2StaticAssets\Contract\ProgressReporterInterface;
use yii\web\AssetBundle;

final readonly class AstAssetBundleFinder implements AssetBundleFinderInterface
{
    public function __construct(
        private PhpFileCollector $fileCollector,
        private PhpClassExtractor $classExtractor,
        private ProgressReporterInterface $progressReporter,
    ) {}

    public function find(): AssetBundleDiscoveryResult
    {
        $files = $this->fileCollector->collect();
        $totalFiles = \count($files);

        $this->progressReporter->start(
            'Scanning PHP files',
            $totalFiles,
        );

        /** @var array<string, DiscoveredClass> $classes */
        $classes = [];

        foreach ($files as $index => $filePath) {
            foreach ($this->classExtractor->extract($filePath) as $class) {
                $classes[$class->className] = $class;
            }

            $this->progressReporter->advance(
                $index + 1,
                $totalFiles,
            );
        }

        $bundleClasses = [];
        $bundleFiles = [];

        foreach ($classes as $className => $class) {
            if (
                $class->abstract
                || !$this->extendsAssetBundle($className, $classes)
            ) {
                continue;
            }

            /** @var class-string<AssetBundle> $className */
            $bundleClasses[$className] = $className;
            $bundleFiles[$class->filePath] = $class->filePath;
        }

        \ksort($bundleClasses);
        \sort($bundleFiles);

        $this->progressReporter->finish(
            \sprintf(
                'Discovered %d AssetBundle classes.',
                \count($bundleClasses),
            ),
        );

        return new AssetBundleDiscoveryResult(
            bundleClasses: \array_values($bundleClasses),
            bundleFiles: $bundleFiles,
            scannedFileCount: $totalFiles,
        );
    }

    /**
     * @param array<string, DiscoveredClass> $classes
     * @param array<string, true>            $visited
     */
    private function extendsAssetBundle(
        string $className,
        array $classes,
        array $visited = [],
    ): bool {
        if (isset($visited[$className])) {
            return false;
        }

        $visited[$className] = true;
        $class = $classes[$className] ?? null;

        if ($class === null || $class->parentClassName === null) {
            return false;
        }

        $parentClassName = \ltrim(
            $class->parentClassName,
            '\\',
        );

        if ($parentClassName === AssetBundle::class) {
            return true;
        }

        if (!isset($classes[$parentClassName])) {
            return false;
        }

        return $this->extendsAssetBundle(
            $parentClassName,
            $classes,
            $visited,
        );
    }
}
