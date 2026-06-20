<?php

declare(strict_types=1);

namespace SolasWyrd\Yii2StaticAssets\Discovery;

use yii\web\AssetBundle;

final readonly class AssetBundleDiscoverer
{
    public function __construct(
        private PhpFileProvider $fileProvider,
        private PhpClassExtractor $classExtractor,
    ) {
    }

    /**
     * @return list<class-string<AssetBundle>>
     */
    public function discover(): array
    {
        /** @var array<string, DiscoveredClass> $classes */
        $classes = [];

        foreach ($this->fileProvider->files() as $filePath) {
            foreach ($this->classExtractor->extract($filePath) as $class) {
                $classes[$class->className] = $class;
            }
        }

        $assetBundleClasses = [];

        foreach ($classes as $className => $class) {
            if ($class->abstract || !$this->extendsAssetBundle($className, $classes)) {
                continue;
            }

            if (!\class_exists($className)) {
                continue;
            }

            if (!\is_subclass_of($className, AssetBundle::class)) {
                continue;
            }

            /** @var class-string<AssetBundle> $className */
            $assetBundleClasses[$className] = $className;
        }

        \ksort($assetBundleClasses);

        return \array_values($assetBundleClasses);
    }

    /**
     * @param array<string, DiscoveredClass> $classes
     * @param array<string, true> $visited
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

        $parentClassName = \ltrim($class->parentClassName, '\\');

        if ($parentClassName === AssetBundle::class) {
            return true;
        }

        if (isset($classes[$parentClassName])) {
            return $this->extendsAssetBundle($parentClassName, $classes, $visited);
        }

        return \class_exists($parentClassName)
            && \is_a($parentClassName, AssetBundle::class, true);
    }
}
