<?php

declare(strict_types=1);

namespace SolasWyrd\Yii2StaticAssets\Tests\Configuration;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SolasWyrd\Yii2StaticAssets\Configuration\StaticAssetsConfiguration;

final class StaticAssetsConfigurationTest extends TestCase
{
    public function testBuildsManifestPathWithoutDuplicateSeparator(): void
    {
        $configuration = self::createConfiguration(
            targetPath: '/app/web/assets/builds/',
            manifestFileName: 'manifest.json',
        );

        self::assertSame(
            '/app/web/assets/builds/manifest.json',
            \str_replace('\\', '/', $configuration->manifestPath()),
        );
    }

    #[DataProvider('invalidConfigurationProvider')]
    public function testRejectsInvalidConfiguration(\Closure $factory, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        $factory();
    }

    /** @return iterable<string, array{\Closure(): StaticAssetsConfiguration, string}> */
    public static function invalidConfigurationProvider(): iterable
    {
        yield 'empty project root' => [static fn (): StaticAssetsConfiguration => self::createConfiguration(projectRoot: ''), 'Project root is required.'];

        yield 'empty target' => [static fn (): StaticAssetsConfiguration => self::createConfiguration(targetPath: ''), 'Target path is required.'];

        yield 'empty allowed root' => [static fn (): StaticAssetsConfiguration => self::createConfiguration(allowedBuildRoot: ''), 'Allowed build root is required.'];

        yield 'empty base URL' => [static fn (): StaticAssetsConfiguration => self::createConfiguration(baseUrl: ''), 'Base URL is required.'];

        yield 'empty scan paths' => [static fn (): StaticAssetsConfiguration => self::createConfiguration(scanPaths: []), 'At least one scan path is required.'];

        yield 'empty hash roots' => [static fn (): StaticAssetsConfiguration => self::createConfiguration(hashRoots: []), 'At least one hash root is required.'];

        yield 'empty manifest name' => [static fn (): StaticAssetsConfiguration => self::createConfiguration(manifestFileName: ''), 'Manifest file name must be a plain file name.'];

        yield 'manifest traversal' => [static fn (): StaticAssetsConfiguration => self::createConfiguration(manifestFileName: '../manifest.json'), 'Manifest file name must be a plain file name.'];

        yield 'manifest subdirectory' => [static fn (): StaticAssetsConfiguration => self::createConfiguration(manifestFileName: 'nested/manifest.json'), 'Manifest file name must be a plain file name.'];

        yield 'invalid suggestion threshold' => [static fn (): StaticAssetsConfiguration => self::createConfiguration(suggestionMinimumPhpFiles: 0), 'Suggestion minimum PHP files must be positive.'];
    }

    /**
     * @param list<string>          $scanPaths
     * @param list<string>          $excludedPatterns
     * @param array<string, string> $hashRoots
     */
    private static function createConfiguration(
        string $projectRoot = '/app',
        string $targetPath = '/app/web/assets/builds',
        string $allowedBuildRoot = '/app/web/assets',
        string $baseUrl = '/assets/builds',
        array $scanPaths = ['/app'],
        array $excludedPatterns = [],
        array $hashRoots = ['app' => '/app'],
        string $manifestFileName = 'static-assets-manifest.json',
        int $suggestionMinimumPhpFiles = 50,
    ): StaticAssetsConfiguration {
        return new StaticAssetsConfiguration(
            projectRoot: $projectRoot,
            targetPath: $targetPath,
            allowedBuildRoot: $allowedBuildRoot,
            baseUrl: $baseUrl,
            scanPaths: $scanPaths,
            excludedPatterns: $excludedPatterns,
            hashRoots: $hashRoots,
            manifestFileName: $manifestFileName,
            suggestionMinimumPhpFiles: $suggestionMinimumPhpFiles,
        );
    }
}
