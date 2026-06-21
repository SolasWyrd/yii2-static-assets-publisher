<?php

declare(strict_types=1);

namespace SolasWyrd\Yii2StaticAssets\Manifest;

use JsonException;
use RuntimeException;
use SolasWyrd\Yii2StaticAssets\Configuration\StaticAssetsConfiguration;
use SolasWyrd\Yii2StaticAssets\Contract\AssetManifestWriterInterface;
use Throwable;

final readonly class JsonFileAssetManifestWriter implements AssetManifestWriterInterface
{
    public function __construct(
        private StaticAssetsConfiguration $configuration,
    ) {}

    public function write(array $entries): void
    {
        $manifestPath = $this->configuration->manifestPath();
        $directoryPath = \dirname($manifestPath);
        $this->ensureDirectoryExists($directoryPath);
        \ksort($entries);

        try {
            $json = \json_encode(
                $entries,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
            );
        } catch (JsonException $exception) {
            throw new RuntimeException(
                'Unable to encode asset manifest.',
                previous: $exception,
            );
        }

        $temporaryPath = $manifestPath . '.tmp-' . \bin2hex(\random_bytes(8));
        $backupPath = $manifestPath . '.bak';

        if (\file_put_contents($temporaryPath, $json . PHP_EOL, LOCK_EX) === false) {
            throw new RuntimeException(
                \sprintf('Unable to write asset manifest temporary file "%s".', $temporaryPath),
            );
        }

        try {
            $this->replaceManifest($manifestPath, $temporaryPath, $backupPath);
        } catch (Throwable $exception) {
            @\unlink($temporaryPath);

            throw $exception;
        }
    }

    private function ensureDirectoryExists(string $directoryPath): void
    {
        if (\is_dir($directoryPath)) {
            return;
        }

        if (!@\mkdir($directoryPath, 0775, true) && !\is_dir($directoryPath)) {
            throw new RuntimeException(
                \sprintf('Unable to create asset manifest directory "%s".', $directoryPath),
            );
        }
    }

    private function replaceManifest(
        string $manifestPath,
        string $temporaryPath,
        string $backupPath,
    ): void {
        $hasExistingManifest = \is_file($manifestPath);

        if (\is_file($backupPath) && !@\unlink($backupPath)) {
            throw new RuntimeException(
                \sprintf('Unable to remove stale asset manifest backup "%s".', $backupPath),
            );
        }

        if ($hasExistingManifest && !@\rename($manifestPath, $backupPath)) {
            throw new RuntimeException(
                \sprintf('Unable to back up existing asset manifest "%s".', $manifestPath),
            );
        }

        if (@\rename($temporaryPath, $manifestPath)) {
            if (\is_file($backupPath) && !@\unlink($backupPath)) {
                throw new RuntimeException(
                    \sprintf(
                        'Asset manifest was written, but backup "%s" could not be removed.',
                        $backupPath,
                    ),
                );
            }

            return;
        }

        if ($hasExistingManifest && \is_file($backupPath) && !@\rename($backupPath, $manifestPath)) {
            throw new RuntimeException(
                \sprintf(
                    'Unable to activate new manifest and restore previous manifest "%s".',
                    $manifestPath,
                ),
            );
        }

        throw new RuntimeException(
            \sprintf('Unable to activate asset manifest "%s".', $manifestPath),
        );
    }
}
