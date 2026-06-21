<?php

declare(strict_types=1);

namespace SolasWyrd\Yii2StaticAssets\Tests\Support;

use SolasWyrd\Yii2StaticAssets\Contract\ProgressReporterInterface;

final class RecordingProgressReporter implements ProgressReporterInterface
{
    /** @var list<array{label:string,total:int}> */
    public array $starts = [];

    /** @var list<array{processed:int,total:int}> */
    public array $advances = [];

    /** @var list<string> */
    public array $finishes = [];

    public function start(string $label, int $total): void
    {
        $this->starts[] = ['label' => $label, 'total' => $total];
    }

    public function advance(int $processed, int $total): void
    {
        $this->advances[] = ['processed' => $processed, 'total' => $total];
    }

    public function finish(string $message): void
    {
        $this->finishes[] = $message;
    }
}
