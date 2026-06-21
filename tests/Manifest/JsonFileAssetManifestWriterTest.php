<?php

declare(strict_types=1);

namespace SolasWyrd\Yii2StaticAssets\Tests\Manifest;

use SolasWyrd\Yii2StaticAssets\Configuration\StaticAssetsConfiguration;
use SolasWyrd\Yii2StaticAssets\Manifest\JsonFileAssetManifestWriter;
use SolasWyrd\Yii2StaticAssets\Tests\Support\TemporaryDirectoryTestCase;

final class JsonFileAssetManifestWriterTest extends TemporaryDirectoryTestCase
{
    public function testCreatesDirectoryAndWritesSortedPrettyJson(): void
    {
        $configuration = $this->configuration();
        $writer = new JsonFileAssetManifestWriter($configuration);
        $writer->write(['vendor/z' => '2', 'app/a' => '1']);

        self::assertSame(
            "{\n    \"app/a\": \"1\",\n    \"vendor/z\": \"2\"\n}\n",
            \file_get_contents($configuration->manifestPath()),
        );
    }

    public function testReplacesExistingManifestWithoutLeavingTemporaryFiles(): void
    {
        $configuration = $this->configuration();
        $writer = new JsonFileAssetManifestWriter($configuration);
        $writer->write(['old' => 'hash']);
        $writer->write(['new' => 'hash']);

        self::assertStringContainsString('"new": "hash"', (string)\file_get_contents($configuration->manifestPath()));
        self::assertFileDoesNotExist($configuration->manifestPath() . '.bak');
        self::assertSame([], \glob($configuration->manifestPath() . '.tmp-*') ?: []);
    }

    private function configuration(): StaticAssetsConfiguration
    {
        return new StaticAssetsConfiguration(
            targetPath: $this->temporaryDirectory . '/nested/builds',
            allowedBuildRoot: $this->temporaryDirectory,
            baseUrl: '/assets',
            scanPaths: [$this->temporaryDirectory],
            hashRoots: ['app' => $this->temporaryDirectory],
        );
    }
}
