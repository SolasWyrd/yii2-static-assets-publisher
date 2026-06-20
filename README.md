# Yii2 Static Assets Publisher

Пакет автоматически находит все конкретные классы, наследующие `yii\web\AssetBundle`, и публикует их во время сборки приложения.

## Установка

```bash
composer require solas-wyrd/yii2-static-assets-publisher
```

Перед публикацией рекомендуется сформировать оптимизированный Composer classmap:

```bash
composer dump-autoload --optimize --classmap-authoritative
```

## Интеграция

Готовый Yii2 console controller находится в:

```text
examples/AssetBuildController.php
```

Скопируйте его в `commands/AssetBuildController.php` и при необходимости скорректируйте исключения.

Запуск:

```bash
php yii asset-build/publish
```

## Docker

```dockerfile
RUN composer dump-autoload --optimize --classmap-authoritative \
    && php yii asset-build/publish
```

## Автоматическое версионирование

`ContentAssetPathHasher` строит имя publish-директории из:

- логического пути source-каталога;
- относительных имён файлов;
- содержимого каждого файла.

Hash автоматически меняется, когда файл:

- добавлен;
- удалён;
- переименован;
- изменён.

Время изменения файла не используется, поэтому результат воспроизводим между Docker-сборками. Вычисленный hash кэшируется в памяти процесса.

Тот же `ContentAssetPathHasher` необходимо использовать в runtime-конфигурации Yii `assetManager`, чтобы web-приложение генерировало те же URL, что и build-команда.

Пример:

```php
use SolasWyrd\Yii2StaticAssets\Hash\ContentAssetPathHasher;

$hasher = new ContentAssetPathHasher([
    'app' => (string) Yii::getAlias('@app'),
    'vendor' => (string) Yii::getAlias('@vendor'),
]);

return [
    'basePath' => '@webroot/assets/builds',
    'baseUrl' => '/assets/builds',
    'appendTimestamp' => false,
    'linkAssets' => false,
    'forceCopy' => false,
    'hashCallback' => $hasher(...),
];
```

## Поиск AssetBundle

Поиск выполняется в два этапа:

1. Быстрый режим читает `vendor/composer/autoload_classmap.php`.
2. Если classmap отсутствует или пуст, выполняется рекурсивный обход заданных корней.

Строится граф наследования, поэтому обнаруживаются классы, наследующие промежуточные базовые AssetBundle. Абстрактные классы не публикуются.

## Безопасность

Перед публикацией целевая директория полностью удаляется и создаётся заново. `BuildDirectory` разрешает только путь вида `.../assets/builds`.

Пакет рассчитан на запуск во время Docker build или другого build pipeline. При ошибке команда должна вернуть ненулевой exit code, чтобы образ не был опубликован.

## Ограничения

- Динамически объявленные через `eval()` классы не поддерживаются.
- Символические ссылки внутри source-каталогов не хешируются и не обходятся.
- Оптимальный режим требует `composer dump-autoload -o`.
