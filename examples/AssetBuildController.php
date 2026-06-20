<?php

declare(strict_types=1);

namespace app\commands;

use SolasWyrd\Yii2StaticAssets\AssetPublisher;
use SolasWyrd\Yii2StaticAssets\AssetPublisherRequest;
use SolasWyrd\Yii2StaticAssets\BuildDirectory;
use SolasWyrd\Yii2StaticAssets\Discovery\AssetBundleDiscoverer;
use SolasWyrd\Yii2StaticAssets\Discovery\ComposerClassMapFileProvider;
use SolasWyrd\Yii2StaticAssets\Discovery\FallbackPhpFileProvider;
use SolasWyrd\Yii2StaticAssets\Discovery\PhpClassExtractor;
use SolasWyrd\Yii2StaticAssets\Discovery\RecursivePhpFileProvider;
use SolasWyrd\Yii2StaticAssets\Exception\AssetPublicationException;
use SolasWyrd\Yii2StaticAssets\Hash\ContentAssetPathHasher;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;
use yii\helpers\FileHelper;

final class AssetBuildController extends Controller
{
    public function actionPublish(): int
    {
        try {
            $appPath = FileHelper::normalizePath((string) Yii::getAlias('@app'));
            $vendorPath = FileHelper::normalizePath((string) Yii::getAlias('@vendor'));
            $targetPath = FileHelper::normalizePath((string) Yii::getAlias('@webroot/assets/builds'));

            $excludedPatterns = [
                '*/.git/*',
                '*/node_modules/*',
                '*/runtime/*',
                '*/web/assets/*',
                '*/tests/*',
                '*/Test/*',
                '*/docs/*',
                '*/examples/*',
                '*/vendor/bin/*',
                '*/vendor/composer/*',
                '*/vendor/phpunit/*',
                '*/vendor/phpstan/*',
                '*/vendor/codeception/*',
            ];

            $fileProvider = new FallbackPhpFileProvider(
                primary: new ComposerClassMapFileProvider(
                    classMapFile: $vendorPath . '/composer/autoload_classmap.php',
                    includedRoots: [$appPath, $vendorPath],
                    excludedPatterns: $excludedPatterns,
                ),
                fallback: new RecursivePhpFileProvider(
                    roots: [$appPath, $vendorPath],
                    excludedPatterns: $excludedPatterns,
                ),
            );

            $hasher = new ContentAssetPathHasher(
                roots: ['app' => $appPath, 'vendor' => $vendorPath],
            );

            $publisher = new AssetPublisher(
                discoverer: new AssetBundleDiscoverer($fileProvider, new PhpClassExtractor()),
                buildDirectory: new BuildDirectory(),
                hashCallback: $hasher(...),
            );

            $result = $publisher->publish(
                new AssetPublisherRequest($targetPath, '/assets/builds'),
            );

            $this->stdout(
                \sprintf("Published %d AssetBundle classes.\n", $result->publishedBundleCount()),
                Console::FG_GREEN,
            );

            return ExitCode::OK;
        } catch (AssetPublicationException $exception) {
            $this->stderr($exception->getMessage() . PHP_EOL, Console::FG_RED);

            if ($exception->getPrevious() !== null) {
                $this->stderr($exception->getPrevious()->getMessage() . PHP_EOL, Console::FG_RED);
            }

            return ExitCode::UNSPECIFIED_ERROR;
        }
    }
}
