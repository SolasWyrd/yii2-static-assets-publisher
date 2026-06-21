<?php

declare(strict_types=1);

namespace SolasWyrd\Yii2StaticAssets\Hash;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SolasWyrd\Yii2StaticAssets\Configuration\StaticAssetsConfiguration;
use SolasWyrd\Yii2StaticAssets\Contract\AssetSourceHasherInterface;
use SplFileInfo;

final readonly class ContentAssetSourceHasher implements AssetSourceHasherInterface
{
    public function __construct(
        private StaticAssetsConfiguration $configuration,
        private int $length = 16,
    ) {
        if ($length < 1 || $length > 64) {
            throw new RuntimeException('Hash length must be between 1 and 64 characters.');
        }
    }

    public function sourceKey(string $sourcePath): string
    {
        $sourcePath = $this->normalize($sourcePath);
        $roots = $this->configuration->hashRoots;
        \uasort(
            $roots,
            static fn (string $first, string $second): int =>
                \strlen($second) <=> \strlen($first),
        );

        foreach ($roots as $name => $root) {
            $root = $this->normalize($root);

            if ($sourcePath === $root) {
                return $name;
            }

            if (\str_starts_with($sourcePath, $root . '/')) {
                return $name . '/' . \substr($sourcePath, \strlen($root) + 1);
            }
        }

        return $sourcePath;
    }

    public function hash(string $sourcePath): string
    {
        $sourcePath = $this->normalize($sourcePath);

        if (!\file_exists($sourcePath)) {
            throw new RuntimeException(
                \sprintf('Asset source path "%s" does not exist.', $sourcePath),
            );
        }

        if (!\is_readable($sourcePath)) {
            throw new RuntimeException(
                \sprintf('Asset source path "%s" is not readable.', $sourcePath),
            );
        }

        $context = \hash_init('sha256');
        \hash_update($context, $this->sourceKey($sourcePath) . "\0");

        if (\is_dir($sourcePath)) {
            $files = $this->collectFiles($sourcePath);

            foreach ($files as $file) {
                $relativePath = \substr($file, \strlen($sourcePath) + 1);
                \hash_update($context, $relativePath . "\0");

                if (!\hash_update_file($context, $file)) {
                    throw new RuntimeException(
                        \sprintf('Unable to hash asset file "%s".', $file),
                    );
                }
            }
        } elseif (!\hash_update_file($context, $sourcePath)) {
            throw new RuntimeException(
                \sprintf('Unable to hash asset file "%s".', $sourcePath),
            );
        }

        return \substr(\hash_final($context), 0, $this->length);
    }

    /** @return list<string> */
    private function collectFiles(string $directory): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $directory,
                RecursiveDirectoryIterator::SKIP_DOTS,
            ),
        );

        foreach ($iterator as $file) {
            if ($file instanceof SplFileInfo && $file->isFile()) {
                $files[] = $this->normalize($file->getPathname());
            }
        }

        \sort($files);

        return $files;
    }

    private function normalize(string $path): string
    {
        return \rtrim(\str_replace('\\', '/', $path), '/');
    }
}
