<?php

namespace codemonauts\aws;

use codemonauts\aws\components\S3AssetManager;
use codemonauts\aws\models\Settings;
use craft\base\Plugin;
use Craft;
use craft\events\AssetEvent;
use craft\events\AssetThumbEvent;
use craft\services\Assets;
use yii\base\Event;

class Aws extends Plugin
{
    /**
     * @inheritDoc
     */
    public function init()
    {
        parent::init();

        Craft::$app->set('assetManager', function() {
            $generalConfig = Craft::$app->getConfig()->getGeneral();
            $config = [
                'class' => S3AssetManager::class,
                'basePath' => $generalConfig->resourceBasePath,
                'baseUrl' => $generalConfig->resourceBaseUrl,
                'appendTimestamp' => false,
            ];

            return Craft::createObject($config);
        });

        Event::on(Assets::class, Assets::EVENT_GET_ASSET_THUMB_URL)
    }

    /**
     * @inheritdoc
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }
}
