<?php

declare(strict_types=1);

namespace SolasWyrd\Yii2StaticAssets\Tests\Discovery;

use SolasWyrd\Yii2StaticAssets\Discovery\PhpClassExtractor;
use SolasWyrd\Yii2StaticAssets\Exception\AssetDiscoveryException;
use SolasWyrd\Yii2StaticAssets\Tests\Support\TemporaryDirectoryTestCase;

final class PhpClassExtractorTest extends TemporaryDirectoryTestCase
{
    public function testExtractsResolvedParentAndAbstractFlag(): void
    {
        $file = $this->createFile('Asset.php', <<<'PHP'
<?php
namespace Example;
use yii\web\AssetBundle as YiiAssetBundle;
abstract class BaseAsset extends YiiAssetBundle {}
final class AppAsset extends BaseAsset {}
PHP);

        $classes = (new PhpClassExtractor())->extract($file);

        self::assertCount(2, $classes);
        self::assertSame('Example\\BaseAsset', $classes[0]->className);
        self::assertSame('yii\\web\\AssetBundle', $classes[0]->parentClassName);
        self::assertTrue($classes[0]->abstract);
        self::assertSame('Example\\AppAsset', $classes[1]->className);
        self::assertSame('Example\\BaseAsset', $classes[1]->parentClassName);
        self::assertFalse($classes[1]->abstract);
    }

    public function testReturnsEmptyListForFileWithoutClassInheritance(): void
    {
        $file = $this->createFile(
            'function.php',
            '<?php function value(): int { return 1; }',
        );

        self::assertSame([], (new PhpClassExtractor())->extract($file));
    }

    public function testFailsFastOnInvalidPhpSyntax(): void
    {
        $file = $this->createFile('Broken.php', '<?php class Broken extends {');

        $this->expectException(AssetDiscoveryException::class);
        $this->expectExceptionMessage($file);

        (new PhpClassExtractor())->extract($file);
    }

    public function testFailsWithoutNativeWarningWhenFileDoesNotExist(): void
    {
        $missingFile = $this->temporaryDirectory . '/missing.php';

        $this->expectException(AssetDiscoveryException::class);
        $this->expectExceptionMessage('does not exist');

        (new PhpClassExtractor())->extract($missingFile);
    }
}
