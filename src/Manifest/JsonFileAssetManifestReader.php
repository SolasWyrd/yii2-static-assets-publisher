<?php

declare(strict_types=1);

namespace SolasWyrd\Yii2StaticAssets\Manifest;

use JsonException;
use RuntimeException;
use SolasWyrd\Yii2StaticAssets\Configuration\StaticAssetsConfiguration;
use SolasWyrd\Yii2StaticAssets\Contract\AssetManifestReaderInterface;

final class JsonFileAssetManifestReader implements AssetManifestReaderInterface
{
    /** @var array<string,string>|null */
    private ?array $manifest = null;

    public function __construct(
        private readonly StaticAssetsConfiguration $configuration,
    ) {}

    public function read(): array
    {
        if ($this->manifest !== null) {
            return $this->manifest;
        }

        $manifestPath = $this->configuration->manifestPath();

        if (!\is_file($manifestPath)) {
            throw new RuntimeException(
                \sprintf('Asset manifest "%s" not found.', $manifestPath),
            );
        }

        $contents = \file_get_contents($manifestPath);

        if ($contents === false) {
            throw new RuntimeException(
                \sprintf('Unable to read asset manifest "%s".', $manifestPath),
            );
        }

        try {
            $data = \json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException(
                \sprintf('Asset manifest "%s" contains invalid JSON.', $manifestPath),
                previous: $exception,
            );
        }

        if (!\is_array($data)) {
            throw new RuntimeException('Asset manifest must contain a JSON object.');
        }

        $manifest = [];

        foreach ($data as $key => $hash) {
            if (!\is_string($key) || $key === '' || !\is_string($hash) || $hash === '') {
                throw new RuntimeException(
                    'Asset manifest must contain non-empty string keys and values.',
                );
            }

            $manifest[$key] = $hash;
        }

        return $this->manifest = $manifest;
    }
}
