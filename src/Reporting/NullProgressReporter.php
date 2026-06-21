<?php

declare(strict_types=1);

namespace SolasWyrd\Yii2StaticAssets\Reporting;

use SolasWyrd\Yii2StaticAssets\Contract\ProgressReporterInterface;

final class NullProgressReporter implements ProgressReporterInterface
{
    public function start(string $label, int $total): void {}

    public function advance(int $processed, int $total): void {}

    public function finish(string $message): void {}
}
