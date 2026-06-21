<?php

declare(strict_types=1);

namespace SolasWyrd\Yii2StaticAssets\Tests\Support;

use PHPUnit\Framework\TestCase;
use yii\helpers\FileHelper;

abstract class TemporaryDirectoryTestCase extends TestCase
{
    protected string $temporaryDirectory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->temporaryDirectory = \sys_get_temp_dir()
            . '/yii2-static-assets-'
            . \bin2hex(\random_bytes(8));
        self::assertTrue(\mkdir($this->temporaryDirectory, 0777, true));
    }

    protected function tearDown(): void
    {
        FileHelper::removeDirectory($this->temporaryDirectory);
        parent::tearDown();
    }

    protected function createFile(string $relativePath, string $contents = ''): string
    {
        $path = $this->temporaryDirectory . '/' . \ltrim($relativePath, '/');
        $directory = \dirname($path);

        if (!\is_dir($directory)) {
            self::assertTrue(\mkdir($directory, 0777, true));
        }

        self::assertNotFalse(\file_put_contents($path, $contents));

        return $path;
    }
}
