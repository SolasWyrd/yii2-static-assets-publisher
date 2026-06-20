<?php

declare(strict_types=1);

namespace SolasWyrd\Yii2StaticAssets\Discovery;

interface PhpFileProvider
{
    /**
     * @return iterable<string>
     */
    public function files(): iterable;
}
