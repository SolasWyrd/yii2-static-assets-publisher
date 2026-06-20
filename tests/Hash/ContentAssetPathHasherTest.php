<?php

declare(strict_types=1);

namespace SolasWyrd\Yii2StaticAssets\Tests\Hash;

use PHPUnit\Framework\TestCase;
use SolasWyrd\Yii2StaticAssets\Hash\ContentAssetPathHasher;

final class ContentAssetPathHasherTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = \sys_get_temp_dir() . '/yii2-assets-' . \bin2hex(\random_bytes(6));
        \mkdir($this->root, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->root);
    }

    public function testHashIsStableForSameContent(): void
    {
        \mkdir($this->root . '/assets');
        \file_put_contents($this->root . '/assets/app.css', 'body {}');

        $firstHasher = new ContentAssetPathHasher(['app' => $this->root]);
        $secondHasher = new ContentAssetPathHasher(['app' => $this->root]);

        self::assertSame(
            $firstHasher($this->root . '/assets'),
            $secondHasher($this->root . '/assets'),
        );
    }

    public function testHashChangesWhenFileContentChanges(): void
    {
        \mkdir($this->root . '/assets');
        $file = $this->root . '/assets/app.css';
        \file_put_contents($file, 'body {}');

        $firstHasher = new ContentAssetPathHasher(['app' => $this->root]);
        $firstHash = $firstHasher($this->root . '/assets');

        \file_put_contents($file, 'body { color: red; }');

        $secondHasher = new ContentAssetPathHasher(['app' => $this->root]);

        self::assertNotSame($firstHash, $secondHasher($this->root . '/assets'));
    }

    public function testHashChangesWhenFileIsRenamed(): void
    {
        \mkdir($this->root . '/assets');
        \file_put_contents($this->root . '/assets/app.css', 'body {}');

        $firstHasher = new ContentAssetPathHasher(['app' => $this->root]);
        $firstHash = $firstHasher($this->root . '/assets');

        \rename($this->root . '/assets/app.css', $this->root . '/assets/main.css');

        $secondHasher = new ContentAssetPathHasher(['app' => $this->root]);

        self::assertNotSame($firstHash, $secondHasher($this->root . '/assets'));
    }

    private function removeDirectory(string $path): void
    {
        if (!\is_dir($path)) {
            return;
        }

        $items = \scandir($path);

        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $itemPath = $path . '/' . $item;

            if (\is_dir($itemPath)) {
                $this->removeDirectory($itemPath);
            } else {
                \unlink($itemPath);
            }
        }

        \rmdir($path);
    }
}
