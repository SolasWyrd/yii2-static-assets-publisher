<?php

declare(strict_types=1);

namespace SolasWyrd\Yii2StaticAssets\Tests\Manifest;

use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use SolasWyrd\Yii2StaticAssets\Configuration\StaticAssetsConfiguration;
use SolasWyrd\Yii2StaticAssets\Manifest\JsonFileAssetManifestReader;
use SolasWyrd\Yii2StaticAssets\Tests\Support\TemporaryDirectoryTestCase;

final class JsonFileAssetManifestReaderTest extends TemporaryDirectoryTestCase
{
    public function testReadsAndCachesValidatedManifest(): void
    {
        $configuration = $this->configuration();
        $this->createFile('builds/static-assets-manifest.json', '{"app/assets":"first"}');
        $reader = new JsonFileAssetManifestReader($configuration);

        self::assertSame(['app/assets' => 'first'], $reader->read());
        \file_put_contents($configuration->manifestPath(), '{"app/assets":"second"}');
        self::assertSame(['app/assets' => 'first'], $reader->read());
    }

    public function testFailsWhenManifestIsMissing(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('not found');

        (new JsonFileAssetManifestReader($this->configuration()))->read();
    }

    #[DataProvider('invalidManifestProvider')]
    public function testRejectsInvalidManifest(string $contents, string $message): void
    {
        $this->createFile('builds/static-assets-manifest.json', $contents);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($message);

        (new JsonFileAssetManifestReader($this->configuration()))->read();
    }

    /** @return iterable<string, array{string, string}> */
    public static function invalidManifestProvider(): iterable
    {
        yield 'invalid JSON' => ['{', 'contains invalid JSON'];

        yield 'scalar JSON' => ['"hash"', 'must contain a JSON object'];

        yield 'empty key' => ['{"":"hash"}', 'non-empty string keys and values'];

        yield 'empty value' => ['{"app/assets":""}', 'non-empty string keys and values'];

        yield 'array value' => ['{"app/assets":[]}', 'non-empty string keys and values'];
    }

    private function configuration(): StaticAssetsConfiguration
    {
        return new StaticAssetsConfiguration(
            projectRoot: $this->temporaryDirectory,
            targetPath: $this->temporaryDirectory . '/builds',
            allowedBuildRoot: $this->temporaryDirectory,
            baseUrl: '/assets',
            scanPaths: [$this->temporaryDirectory],
            excludedPatterns: [],
            hashRoots: ['app' => $this->temporaryDirectory],
        );
    }
}
