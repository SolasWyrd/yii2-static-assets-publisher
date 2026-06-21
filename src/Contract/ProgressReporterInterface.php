<?php

declare(strict_types=1);

namespace SolasWyrd\Yii2StaticAssets\Contract;

interface ProgressReporterInterface
{
    public function start(string $label, int $total): void;

    public function advance(int $processed, int $total): void;

    public function finish(string $message): void;
}
