<?php

declare(strict_types=1);

namespace SolasWyrd\Yii2StaticAssets\Tests\Hash;

use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use SolasWyrd\Yii2StaticAssets\Configuration\StaticAssetsConfiguration;
use SolasWyrd\Yii2StaticAssets\Hash\ContentAssetSourceHasher;
use SolasWyrd\Yii2StaticAssets\Tests\Support\TemporaryDirectoryTestCase;

final class ContentAssetSourceHasherTest extends TemporaryDirectoryTestCase
{
    public function testProducesStableHashForUnchangedDirectory(): void
    {
        $this->createFile('assets/app.css', 'body{}');
        $hasher = $this->hasher();

        self::assertSame(
            $hasher->hash($this->temporaryDirectory . '/assets'),
            $hasher->hash($this->temporaryDirectory . '/assets'),
        );
    }

    public function testHashChangesWhenContentChanges(): void
    {
        $file = $this->createFile('assets/app.css', 'body{}');
        $hasher = $this->hasher();
        $before = $hasher->hash(\dirname($file));
        \file_put_contents($file, 'body{color:red}');

        self::assertNotSame($before, $hasher->hash(\dirname($file)));
    }

    public function testHashChangesWhenFileNameChanges(): void
    {
        $file = $this->createFile('assets/old.css', 'same');
        $hasher = $this->hasher();
        $before = $hasher->hash(\dirname($file));
        \rename($file, \dirname($file) . '/new.css');

        self::assertNotSame($before, $hasher->hash(\dirname($file)));
    }

    public function testUsesLongestMatchingConfiguredRoot(): void
    {
        $configuration = $this->configuration([
            'app' => $this->temporaryDirectory,
            'package' => $this->temporaryDirectory . '/vendor/package',
        ]);
        $hasher = new ContentAssetSourceHasher($configuration);

        self::assertSame(
            'package/assets',
            $hasher->sourceKey($this->temporaryDirectory . '/vendor/package/assets'),
        );
    }

    #[DataProvider('invalidLengthProvider')]
    public function testRejectsInvalidHashLength(int $length): void
    {
        $this->expectException(RuntimeException::class);

        new ContentAssetSourceHasher($this->configuration(), $length);
    }

    /** @return iterable<array{int}> */
    public static function invalidLengthProvider(): iterable
    {
        yield [0];

        yield [65];
    }

    public function testFailsForMissingSource(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('does not exist');

        $this->hasher()->hash($this->temporaryDirectory . '/missing');
    }

    private function hasher(): ContentAssetSourceHasher
    {
        return new ContentAssetSourceHasher($this->configuration());
    }

    /** @param array<string, string>|null $hashRoots */
    private function configuration(?array $hashRoots = null): StaticAssetsConfiguration
    {
        return new StaticAssetsConfiguration(
            projectRoot: $this->temporaryDirectory,
            targetPath: $this->temporaryDirectory . '/builds',
            allowedBuildRoot: $this->temporaryDirectory,
            baseUrl: '/assets',
            scanPaths: [$this->temporaryDirectory],
            excludedPatterns: [],
            hashRoots: $hashRoots ?? ['app' => $this->temporaryDirectory],
        );
    }
}
