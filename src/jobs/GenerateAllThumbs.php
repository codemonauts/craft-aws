<?php

namespace codemonauts\aws\jobs;

use codemonauts\aws\Aws;
use Craft;
use craft\queue\BaseJob;

class GenerateAllThumbs extends BaseJob
{
    public $assetId;

    /**
     * @inheritDoc
     */
    public function execute($queue)
    {
        $this->setProgress($queue, 1);

        $asset = \Craft::$app->assets->getAssetById($this->assetId);

        $baseUrl = Aws::getInstance()->getSettings()->thumbnailsBaseUrl;
        $meta = Aws::getInstance()->assets->createAllThumbSizes($asset);

        $cachedMeta = [
            'baseUrl' => $baseUrl . '/' . $meta['path'],
            'ext' => $meta['ext'],
        ];

        Craft::$app->cache->set('aws-thumb-' . $asset->id, $cachedMeta, 0);

        return true;
    }

    /**
     * @inheritDoc
     */
    protected function defaultDescription()
    {
        return 'Generate all thumbs for asset';
    }
}
