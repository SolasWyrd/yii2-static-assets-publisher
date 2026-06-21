<?php

declare(strict_types=1);

namespace SolasWyrd\Yii2StaticAssets\Yii;

use SolasWyrd\Yii2StaticAssets\Application\PublishStaticAssetsService;
use Throwable;
use yii\base\Module;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

final class AssetBuildController extends Controller
{
    public function __construct(
        string $id,
        Module $module,
        private readonly PublishStaticAssetsService $publisher,
        array $config = [],
    ) {
        parent::__construct($id, $module, $config);
    }

    public function actionPublish(): int
    {
        try {
            $result = $this->publisher->publish();
            Console::stdout(
                \sprintf(
                    'Published %d AssetBundle classes to "%s" in %.2f s.%s',
                    $result->publishedBundleCount,
                    $result->targetPath,
                    $result->durationSeconds,
                    PHP_EOL,
                ),
            );

            return ExitCode::OK;
        } catch (Throwable $exception) {
            Console::stderr(
                $this->formatExceptionChain($exception) . PHP_EOL,
            );

            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    private function formatExceptionChain(Throwable $exception): string
    {
        $messages = [];

        do {
            $messages[] = $exception->getMessage();
            $exception = $exception->getPrevious();
        } while ($exception !== null);

        return \implode(PHP_EOL, \array_unique($messages));
    }
}
