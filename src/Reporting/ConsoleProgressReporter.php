<?php

declare(strict_types=1);

namespace SolasWyrd\Yii2StaticAssets\Reporting;

use SolasWyrd\Yii2StaticAssets\Contract\ProgressReporterInterface;
use yii\helpers\Console;

final class ConsoleProgressReporter implements ProgressReporterInterface
{
    private const BAR_WIDTH = 32;
    private const NON_INTERACTIVE_STEP = 10;

    private float $startedAt = 0.0;
    private int $lastPercent = -1;
    private string $label = '';
    private bool $interactive = false;

    public function start(string $label, int $total): void
    {
        $this->startedAt = \microtime(true);
        $this->lastPercent = -1;
        $this->label = $label;
        $this->interactive = $this->isInteractiveTerminal();

        if ($this->interactive) {
            $this->renderInteractive(0, $total);

            return;
        }

        Console::stdout(
            \sprintf('[START] %s (%s)%s', $label, $this->formatNumber($total), PHP_EOL),
        );
    }

    public function advance(int $processed, int $total): void
    {
        $processed = \max(0, \min($processed, $total));
        $percent = $total === 0
            ? 100
            : (int)\floor($processed * 100 / $total);

        if ($this->interactive) {
            $this->renderInteractive($processed, $total);

            return;
        }

        if (
            $percent === $this->lastPercent
            || (
                $percent % self::NON_INTERACTIVE_STEP !== 0
                && $processed !== $total
            )
        ) {
            return;
        }

        $this->lastPercent = $percent;
        $elapsed = $this->elapsedSeconds();
        $eta = $this->estimateRemainingSeconds($processed, $total, $elapsed);

        Console::stdout(
            \sprintf(
                '[%3d%%] %s/%s | elapsed %s | ETA %s%s',
                $percent,
                $this->formatNumber($processed),
                $this->formatNumber($total),
                $this->formatDuration($elapsed),
                $this->formatDuration($eta),
                PHP_EOL,
            ),
        );
    }

    public function finish(string $message): void
    {
        $elapsed = $this->elapsedSeconds();

        if ($this->interactive) {
            Console::stdout("\r\033[2K");
            Console::stdout(
                Console::ansiFormat('DONE', [Console::FG_GREEN, Console::BOLD])
                . '  '
                . $message
                . '  '
                . Console::ansiFormat(
                    '(' . $this->formatDuration($elapsed) . ')',
                    [Console::FG_GREY],
                )
                . PHP_EOL,
            );

            return;
        }

        Console::stdout(
            \sprintf('[DONE] %s (%s)%s', $message, $this->formatDuration($elapsed), PHP_EOL),
        );
    }

    private function renderInteractive(int $processed, int $total): void
    {
        $percent = $total === 0
            ? 100
            : (int)\floor($processed * 100 / $total);
        $filledWidth = $total === 0
            ? self::BAR_WIDTH
            : (int)\floor(self::BAR_WIDTH * $processed / $total);
        $filledWidth = \max(0, \min(self::BAR_WIDTH, $filledWidth));

        if ($filledWidth >= self::BAR_WIDTH) {
            $bar = \str_repeat('=', self::BAR_WIDTH);
        } else {
            $bar = \str_repeat('=', $filledWidth)
                . '>'
                . \str_repeat(' ', self::BAR_WIDTH - $filledWidth - 1);
        }

        $elapsed = $this->elapsedSeconds();
        $eta = $this->estimateRemainingSeconds($processed, $total, $elapsed);
        $rate = $elapsed > 0.0 ? $processed / $elapsed : 0.0;

        Console::stdout(
            \sprintf(
                "\r\033[2K%-30s [%s] %3d%%  %s/%s  %s/s  ETA %s",
                $this->label,
                $bar,
                $percent,
                $this->formatNumber($processed),
                $this->formatNumber($total),
                $this->formatNumber((int)\round($rate)),
                $this->formatDuration($eta),
            ),
        );
    }

    private function isInteractiveTerminal(): bool
    {
        return \defined('STDOUT')
            && \function_exists('stream_isatty')
            && \stream_isatty(STDOUT)
            && \getenv('CI') === false;
    }

    private function elapsedSeconds(): float
    {
        return \max(0.0, \microtime(true) - $this->startedAt);
    }

    private function estimateRemainingSeconds(
        int $processed,
        int $total,
        float $elapsed,
    ): float {
        if ($processed <= 0 || $total <= $processed) {
            return 0.0;
        }

        return \max(
            0.0,
            ($elapsed / $processed) * ($total - $processed),
        );
    }

    private function formatDuration(float $seconds): string
    {
        if ($seconds < 10.0) {
            return \number_format($seconds, 1) . 's';
        }

        $roundedSeconds = (int)\round($seconds);
        $minutes = \intdiv($roundedSeconds, 60);
        $remainingSeconds = $roundedSeconds % 60;

        if ($minutes === 0) {
            return $remainingSeconds . 's';
        }

        return \sprintf('%dm %02ds', $minutes, $remainingSeconds);
    }

    private function formatNumber(int $number): string
    {
        return \number_format($number, 0, '.', ',');
    }
}
