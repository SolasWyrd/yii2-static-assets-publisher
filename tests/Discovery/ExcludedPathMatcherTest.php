<?php

declare(strict_types=1);

namespace SolasWyrd\Yii2StaticAssets\Tests\Discovery;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use SolasWyrd\Yii2StaticAssets\Configuration\StaticAssetsConfiguration;
use SolasWyrd\Yii2StaticAssets\Discovery\ExcludedPathMatcher;

final class ExcludedPathMatcherTest extends TestCase
{
    public function testMatchesOnlyConfiguredProjectRelativeDirectory(): void
    {
        $matcher = $this->matcher([
            'vendor/phpstan/phpstan/src/*',
        ]);

        self::assertTrue(
            $matcher->matches('/app/vendor/phpstan/phpstan/src'),
        );
        self::assertTrue(
            $matcher->matches('/app/vendor/phpstan/phpstan/src/Analyser/FileAnalyser.php'),
        );
        self::assertFalse(
            $matcher->matches('/app/vendor/yiisoft/yii2-bootstrap/src/BootstrapAsset.php'),
        );
        self::assertFalse(
            $matcher->matches('/app/src/Application.php'),
        );
    }

    public function testBuildsSpecificPatternFromProjectRelativeDirectory(): void
    {
        self::assertSame(
            'vendor/phpstan/phpstan/src/*',
            $this->matcher([])->patternForDirectory(
                '/app/vendor/phpstan/phpstan/src',
            ),
        );
    }

    public function testRejectsPathOutsideProjectRoot(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('outside project root');

        $this->matcher([])->relativePath('/other/vendor/package');
    }

    /** @param list<string> $excludedPatterns */
    private function matcher(array $excludedPatterns): ExcludedPathMatcher
    {
        $configuration = new StaticAssetsConfiguration(
            projectRoot: '/app',
            targetPath: '/app/web/assets/builds',
            allowedBuildRoot: '/app/web/assets',
            baseUrl: '/assets/builds',
            scanPaths: ['/app'],
            excludedPatterns: $excludedPatterns,
            hashRoots: ['app' => '/app'],
        );

        return new ExcludedPathMatcher($configuration);
    }
}
