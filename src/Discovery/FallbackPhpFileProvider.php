<?php

declare(strict_types=1);

namespace SolasWyrd\Yii2StaticAssets\Discovery;

use Throwable;

final readonly class FallbackPhpFileProvider implements PhpFileProvider
{
    public function __construct(
        private PhpFileProvider $primary,
        private PhpFileProvider $fallback,
    ) {
    }

    public function files(): iterable
    {
        try {
            $files = \iterator_to_array($this->primary->files(), false);

            if ($files !== []) {
                yield from $files;

                return;
            }
        } catch (Throwable) {
            // The recursive provider below is the intended fallback.
        }

        yield from $this->fallback->files();
    }
}
