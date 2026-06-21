<?php

declare(strict_types=1);

namespace SolasWyrd\Yii2StaticAssets\Discovery;

use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SolasWyrd\Yii2StaticAssets\Configuration\StaticAssetsConfiguration;
use SplFileInfo;

/** @internal */
final readonly class PhpFileCollector
{
    public function __construct(
        private StaticAssetsConfiguration $configuration,
    ) {}

    /** @return list<string> */
    public function collect(): array
    {
        $files = [];

        foreach ($this->configuration->scanPaths as $root) {
            if (!\is_dir($root)) {
                continue;
            }

            $directoryIterator = new RecursiveDirectoryIterator(
                $root,
                RecursiveDirectoryIterator::SKIP_DOTS,
            );
            $filterIterator = new RecursiveCallbackFilterIterator(
                $directoryIterator,
                static fn (SplFileInfo $file): bool => $file->isDir()
                    || ($file->isFile() && $file->getExtension() === 'php'),
            );
            $iterator = new RecursiveIteratorIterator(
                $filterIterator,
                RecursiveIteratorIterator::LEAVES_ONLY,
                RecursiveIteratorIterator::CATCH_GET_CHILD,
            );

            foreach ($iterator as $file) {
                if (!$file instanceof SplFileInfo || !$file->isFile()) {
                    continue;
                }

                $files[$this->normalize($file->getPathname())] = true;
            }
        }

        $paths = \array_keys($files);
        \sort($paths);

        return $paths;
    }

    private function normalize(string $path): string
    {
        return \str_replace('\\', '/', $path);
    }
}
