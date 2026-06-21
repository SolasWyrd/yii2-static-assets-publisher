<?php

declare(strict_types=1);

namespace SolasWyrd\Yii2StaticAssets\Tests\Hash;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use SolasWyrd\Yii2StaticAssets\Contract\AssetManifestReaderInterface;
use SolasWyrd\Yii2StaticAssets\Contract\AssetSourceHasherInterface;
use SolasWyrd\Yii2StaticAssets\Hash\ManifestAssetPathHasher;

final class ManifestAssetPathHasherTest extends TestCase
{
    public function testReturnsHashByResolvedSourceKey(): void
    {
        $reader = new class implements AssetManifestReaderInterface {
            public function read(): array
            {
                return ['app/assets' => 'abc123'];
            }
        };
        $hasher = new class implements AssetSourceHasherInterface {
            public function sourceKey(string $sourcePath): string
            {
                return 'app/assets';
            }

            public function hash(string $sourcePath): string
            {
                throw new \LogicException('Runtime hasher must not calculate content hashes.');
            }
        };

        self::assertSame(
            'abc123',
            (new ManifestAssetPathHasher($reader, $hasher))('/app/assets'),
        );
    }

    public function testFailsWhenResolvedSourceKeyIsMissing(): void
    {
        $reader = new class implements AssetManifestReaderInterface {
            public function read(): array
            {
                return [];
            }
        };
        $hasher = new class implements AssetSourceHasherInterface {
            public function sourceKey(string $sourcePath): string
            {
                return 'app/missing';
            }

            public function hash(string $sourcePath): string
            {
                return 'unused';
            }
        };

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('app/missing');

        (new ManifestAssetPathHasher($reader, $hasher))('/app/missing');
    }
}
