# Yii2 Static Assets Publisher

Пакет автоматически находит классы `yii\web\AssetBundle`, публикует их ресурсы во время production-сборки и создаёт manifest с контентными хешами.

[![Latest Stable Version](https://poser.pugx.org/solas-wyrd/yii2-static-assets-publisher/v)](https://packagist.org/packages/solas-wyrd/yii2-static-assets-publisher)
[![Total Downloads](https://poser.pugx.org/solas-wyrd/yii2-static-assets-publisher/downloads)](https://packagist.org/packages/solas-wyrd/yii2-static-assets-publisher)
[![Build status](https://github.com/SolasWyrd/yii2-static-assets-publisher/actions/workflows/build.yml/badge.svg?branch=main)](https://github.com/SolasWyrd/yii2-static-assets-publisher/actions/workflows/build.yml?query=branch%3Amain)
[![Code Coverage](https://codecov.io/gh/SolasWyrd/yii2-static-assets-publisher/branch/main/graph/badge.svg)](https://codecov.io/gh/SolasWyrd/yii2-static-assets-publisher)
[![Static analysis](https://github.com/SolasWyrd/yii2-static-assets-publisher/actions/workflows/static.yml/badge.svg?branch=main)](https://github.com/SolasWyrd/yii2-static-assets-publisher/actions/workflows/static.yml?query=branch%3Amain)

## Требования

- PHP 8.2 или выше;
- Yii2 2.0.49 или выше;

## Установка

```bash
composer require solas-wyrd/yii2-static-assets-publisher
```

## Конфигурация

Создайте файл, например `config/static-assets.php`:

```php
<?php

declare(strict_types=1);

use SolasWyrd\Yii2StaticAssets\Configuration\StaticAssetsConfiguration;

$applicationPath = dirname(__DIR__);
$vendorPath = $applicationPath . '/vendor';

return new StaticAssetsConfiguration(
    targetPath: $applicationPath . '/web/assets/builds',
    allowedBuildRoot: $applicationPath . '/web/assets',
    baseUrl: '/assets/builds',
    scanPaths: [
        $applicationPath,
        $vendorPath,
    ],
    hashRoots: [
        'app' => $applicationPath,
        'vendor' => $vendorPath,
    ],
    manifestFileName: 'static-assets-manifest.json',
);
```

### Параметры

`targetPath` — каталог опубликованных ассетов. Перед публикацией он полностью пересоздаётся.

`allowedBuildRoot` — безопасный родительский каталог. `targetPath` обязан находиться внутри него. Это защищает от удаления произвольного пути и выхода через symlink.

`baseUrl` — публичный URL build-директории.

`scanPaths` — каталоги поиска PHP-классов

`hashRoots` — соответствие логических имён абсолютным корням. Абсолютный путь `/var/www/app/assets` может быть записан в manifest как `app/assets`.

## Yii DI definitions

```php
<?php

declare(strict_types=1);

use SolasWyrd\Yii2StaticAssets\Configuration\StaticAssetsConfiguration;
use SolasWyrd\Yii2StaticAssets\Contract\AssetBundleFinderInterface;
use SolasWyrd\Yii2StaticAssets\Contract\AssetBundlePublisherInterface;
use SolasWyrd\Yii2StaticAssets\Contract\AssetManifestReaderInterface;
use SolasWyrd\Yii2StaticAssets\Contract\AssetManifestWriterInterface;
use SolasWyrd\Yii2StaticAssets\Contract\AssetSourceHasherInterface;
use SolasWyrd\Yii2StaticAssets\Contract\ProgressReporterInterface;
use SolasWyrd\Yii2StaticAssets\Discovery\AstAssetBundleFinder;
use SolasWyrd\Yii2StaticAssets\Hash\ContentAssetSourceHasher;
use SolasWyrd\Yii2StaticAssets\Manifest\JsonFileAssetManifestReader;
use SolasWyrd\Yii2StaticAssets\Manifest\JsonFileAssetManifestWriter;
use SolasWyrd\Yii2StaticAssets\Publication\YiiAssetBundlePublisher;
use SolasWyrd\Yii2StaticAssets\Reporting\ConsoleProgressReporter;

/** @var StaticAssetsConfiguration $configuration */
$configuration = require __DIR__ . '/static-assets.php';

return [
    StaticAssetsConfiguration::class =>
        static fn (): StaticAssetsConfiguration => $configuration,

    AssetBundleFinderInterface::class => AstAssetBundleFinder::class,
    AssetBundlePublisherInterface::class => YiiAssetBundlePublisher::class,
    AssetSourceHasherInterface::class => ContentAssetSourceHasher::class,
    AssetManifestReaderInterface::class => JsonFileAssetManifestReader::class,
    AssetManifestWriterInterface::class => JsonFileAssetManifestWriter::class,
    ProgressReporterInterface::class => ConsoleProgressReporter::class,
];
```

## Console controller

Добавьте контроллер пакета в console-конфигурацию:

```php
<?php

declare(strict_types=1);

use SolasWyrd\Yii2StaticAssets\Yii\AssetBuildController;

return [
    'controllerMap' => [
        'asset-build' => [
            'class' => AssetBuildController::class,
        ],
    ],
];
```

Публикация:

```bash
php yii asset-build/publish
```

## Runtime AssetManager

В web-конфигурации используйте manifest вместо повторного вычисления хешей:

```php
<?php

declare(strict_types=1);

use SolasWyrd\Yii2StaticAssets\Configuration\StaticAssetsConfiguration;
use SolasWyrd\Yii2StaticAssets\Hash\ContentAssetSourceHasher;
use SolasWyrd\Yii2StaticAssets\Hash\ManifestAssetPathHasher;
use SolasWyrd\Yii2StaticAssets\Manifest\JsonFileAssetManifestReader;
use yii\web\AssetManager;

/** @var StaticAssetsConfiguration $configuration */
$configuration = require __DIR__ . '/static-assets.php';

$manifestHasher = new ManifestAssetPathHasher(
    new JsonFileAssetManifestReader($configuration),
    new ContentAssetSourceHasher($configuration),
);

return [
    'components' => [
        'assetManager' => [
            'class' => AssetManager::class,
            'basePath' => $configuration->targetPath,
            'baseUrl' => $configuration->baseUrl,
            'appendTimestamp' => false,
            'linkAssets' => false,
            'forceCopy' => false,
            'hashCallback' => $manifestHasher(...),
        ],
    ],
];
```

Если manifest отсутствует или не содержит source-путь, runtime завершится с исключением. Для production это правильное fail-fast поведение: неполная сборка не маскируется пересчётом файлов.

## Замена реализаций

### Собственный поиск AssetBundle

```php
use SolasWyrd\Yii2StaticAssets\Contract\AssetBundleFinderInterface;

return [
    AssetBundleFinderInterface::class => ProjectAssetBundleFinder::class,
];
```

Это полезно, если проект использует собственный registry, ограниченные namespaces или предварительно сформированный classmap.

### Собственная публикация

```php
use SolasWyrd\Yii2StaticAssets\Contract\AssetBundlePublisherInterface;

return [
    AssetBundlePublisherInterface::class => CdnAssetBundlePublisher::class,
];
```

Реализация может публиковать в shared volume, объектное хранилище или применять дополнительные права и post-processing.

### Другое хранилище manifest

```php
use SolasWyrd\Yii2StaticAssets\Contract\AssetManifestReaderInterface;
use SolasWyrd\Yii2StaticAssets\Contract\AssetManifestWriterInterface;

return [
    AssetManifestReaderInterface::class => RedisAssetManifestReader::class,
    AssetManifestWriterInterface::class => RedisAssetManifestWriter::class,
];
```

### Запуск без консольного прогресса

```php
use SolasWyrd\Yii2StaticAssets\Contract\ProgressReporterInterface;
use SolasWyrd\Yii2StaticAssets\Reporting\NullProgressReporter;

return [
    ProgressReporterInterface::class => NullProgressReporter::class,
];
```

## Manifest

Пример `static-assets-manifest.json`:

```json
{
    "app/assets": "f4d7c8a1b2e39d10",
    "vendor/yiisoft/yii2/assets": "91f8a4bb6c2280e1"
}
```

Изменение содержимого или имени файла меняет хеш и URL опубликованного ресурса. Неизменённый контент сохраняет прежний URL.

## Docker

Публикуйте ассеты после установки зависимостей и копирования исходников:

```dockerfile
WORKDIR /var/www/html

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress

COPY . .

RUN composer dump-autoload \
        --optimize \
        --classmap-authoritative \
    && php yii asset-build/publish
```

## Разработка пакета

```bash
composer install
composer validate --strict
composer test
composer analyse
composer cs-check
```

Автоматическое исправление code style:

```bash
composer cs-fix
```

## Лицензия

MIT.
