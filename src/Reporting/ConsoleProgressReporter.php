<?php

declare(strict_types=1);

namespace SolasWyrd\Yii2StaticAssets\Reporting;

use SolasWyrd\Yii2StaticAssets\Contract\ProgressReporterInterface;
use yii\helpers\Console;

final class ConsoleProgressReporter implements ProgressReporterInterface
{
    private float $startedAt = 0.0;
    private int $lastPercent = -1;

    public function start(string $label, int $total): void
    {
        $this->startedAt = \microtime(true);
        $this->lastPercent = -1;
        Console::stdout($label . '...' . PHP_EOL);
    }

    public function advance(int $processed, int $total): void
    {
        $percent = $total === 0 ? 100 : (int)\floor($processed * 100 / $total);

        if ($percent === $this->lastPercent || ($percent % 5 !== 0 && $processed !== $total)) {
            return;
        }

        $this->lastPercent = $percent;
        $elapsed = \microtime(true) - $this->startedAt;
        $eta = $processed > 0
            ? \max(0.0, ($elapsed / $processed) * ($total - $processed))
            : 0.0;

        Console::stdout(
            \sprintf(
                "  %3d%% %d/%d | remaining %d | elapsed %.1fs | ETA %.1fs\n",
                $percent,
                $processed,
                $total,
                \max(0, $total - $processed),
                $elapsed,
                $eta,
            ),
        );
    }

    public function finish(string $message): void
    {
        Console::stdout($message . PHP_EOL);
    }
}
