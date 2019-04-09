<?php

namespace codemonauts\aws\console\controllers;

use codemonauts\aws\Aws;
use codemonauts\aws\jobs\GenerateAllThumbs;
use craft\elements\Asset;
use craft\helpers\Console;
use yii\console\{Controller, ExitCode};
use Craft;

/**
 * Generate thumbs of assets.
 */
class ThumbsController extends Controller
{
    public $defaultAction = 'generate';

    /**
     * Dispatches jobs to generate all thumbs for one or all assets.
     *
     * @param bool $all Whether all assets should be processed.
     * @param int|null $assetId Specify one asset by ID to generate thumbs for.
     *
     * @return int
     */
    public function actionGenerate($all = false, $assetId = null): int
    {
        if (!Aws::getInstance()->getSettings()->thumbnailsOnBucket) {
            $this->stderr('Storing thumbnails into a bucket is not enabled!'.PHP_EOL, Console::FG_RED);
            return ExitCode::OK;
        }

        if ($all === false && $assetId === null)
        {
            $this->stderr('You forgot to say what to do!'.PHP_EOL, Console::FG_RED);
            return ExitCode::OK;
        }

        if ($all) {
            $assets = Asset::find()
                ->anyStatus()
                ->ids();
        } else {
            $assets = [$assetId];
        }

        $count = count($assets);
        $counter = 0;

        Console::startProgress($counter, $count, 'Dispatching jobs');

        foreach ($assets as $asset) {
            Craft::$app->queue->push(new GenerateAllThumbs([
                'assetId' => $asset,
            ]));

            Console::updateProgress(++$counter, $count);
        }

        Console::endProgress(true, false);

        $this->stdout('Dispatched '.$count.' jobs.'.PHP_EOL, Console::FG_GREEN);
        return ExitCode::OK;
    }

    public function actionGenerateLive($all = false, $assetId = null): int
    {
        if (!Aws::getInstance()->getSettings()->thumbnailsOnBucket) {
            $this->stderr('Storing thumbnails into a bucket is not enabled!'.PHP_EOL, Console::FG_RED);
            return ExitCode::OK;
        }

        if ($all === false && $assetId === null)
        {
            $this->stderr('You forgot to say what to do!'.PHP_EOL, Console::FG_RED);
            return ExitCode::OK;
        }

        if ($all) {
            $assets = Asset::find()
                ->anyStatus()
                ->ids();
        } else {
            $assets = [$assetId];
        }

        $count = count($assets);
        $counter = 0;

        $baseUrl = Aws::getInstance()->getSettings()->thumbnailsBaseUrl;

        Console::startProgress($counter, $count, 'Generating thumbs');

        foreach ($assets as $assetId) {

            $asset = Craft::$app->assets->getAssetById($assetId);

            $meta = Aws::getInstance()->assets->createAllThumbSizes($asset);

            $cachedMeta = [
                'baseUrl' => $baseUrl . '/' . $meta['path'],
                'ext' => $meta['ext'],
            ];

            Craft::$app->cache->set('aws-thumb-' . $asset->id, $cachedMeta, 0);

            unset($asset);
            unset($meta);

            Console::updateProgress(++$counter, $count);
        }

        Console::endProgress(true, false);

        $this->stdout('Generated thumbs for '.$count.' assets.'.PHP_EOL, Console::FG_GREEN);
        return ExitCode::OK;
    }
}
