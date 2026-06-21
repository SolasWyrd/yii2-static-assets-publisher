<?php

declare(strict_types=1);

namespace SolasWyrd\Yii2StaticAssets\Yii;

use SolasWyrd\Yii2StaticAssets\Application\AnalyzeAssetExclusionsService;
use SolasWyrd\Yii2StaticAssets\Application\PublishStaticAssetsService;
use SolasWyrd\Yii2StaticAssets\Result\ExclusionSuggestion;
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
        private readonly AnalyzeAssetExclusionsService $analyzer,
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

    public function actionAnalyzeExclusions(): int
    {
        try {
            $result = $this->analyzer->analyze();
            Console::stdout(
                \sprintf(
                    "PHP files: %d total, %d already excluded, %d analyzed.\n"
                    . "AssetBundle classes found: %d. Analysis completed in %.2f s.\n\n",
                    $result->totalPhpFiles,
                    $result->excludedPhpFiles,
                    $result->analyzedPhpFiles,
                    $result->assetBundleCount,
                    $result->durationSeconds,
                ),
            );
            Console::stdout("Configured patterns:\n");

            foreach ($result->configuredPatternMatches as $pattern => $count) {
                Console::stdout(
                    \sprintf(
                        "  [%d] %s%s\n",
                        $count,
                        $pattern,
                        $count === 0 ? '  (unused)' : '',
                    ),
                );
            }

            Console::stdout("\nSuggested new patterns:\n");

            foreach ($result->suggestions as $suggestion) {
                Console::stdout(
                    \sprintf(
                        "  %s — %d PHP files (%.1f%%), %s\n    %s\n",
                        $suggestion->pattern,
                        $suggestion->phpFileCount,
                        $suggestion->percentage,
                        $suggestion->vendorSafe
                            ? 'safe vendor candidate'
                            : 'review required',
                        $suggestion->directory,
                    ),
                );
            }

            $snippet = $this->formatExcludedPatterns($result->suggestions);

            if ($snippet !== '') {
                Console::stdout(PHP_EOL . $snippet);
            }

            return ExitCode::OK;
        } catch (Throwable $exception) {
            Console::stderr(
                $this->formatExceptionChain($exception) . PHP_EOL,
            );

            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    /** @param list<ExclusionSuggestion> $suggestions */
    private function formatExcludedPatterns(array $suggestions): string
    {
        if ($suggestions === []) {
            return '';
        }

        $lines = ['Copy-paste into excludedPatterns:'];

        foreach ($suggestions as $suggestion) {
            $lines[] = \sprintf("        '%s',", $suggestion->pattern);
        }

        return \implode(PHP_EOL, $lines) . PHP_EOL;
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
