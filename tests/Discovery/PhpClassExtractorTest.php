<?php

declare(strict_types=1);

namespace SolasWyrd\Yii2StaticAssets\Tests\Discovery;

use SolasWyrd\Yii2StaticAssets\Discovery\PhpClassExtractor;
use PHPUnit\Framework\TestCase;

final class PhpClassExtractorTest extends TestCase
{
    public function testExtractsResolvedParentAndAbstractFlag(): void
    {
        $path = \tempnam(\sys_get_temp_dir(), 'asset-parser-');
        self::assertIsString($path);

        \file_put_contents($path, <<<'PHP'
<?php
namespace Demo;
use yii\web\AssetBundle as BaseAssetBundle;
abstract class BaseAsset extends BaseAssetBundle {}
final class AppAsset extends BaseAsset {}
PHP);

        $classes = (new PhpClassExtractor())->extract($path);
        @\unlink($path);

        self::assertCount(2, $classes);
        self::assertSame('Demo\\BaseAsset', $classes[0]->className);
        self::assertSame('yii\\web\\AssetBundle', $classes[0]->parentClassName);
        self::assertTrue($classes[0]->abstract);
        self::assertSame('Demo\\AppAsset', $classes[1]->className);
        self::assertSame('Demo\\BaseAsset', $classes[1]->parentClassName);
    }
}
