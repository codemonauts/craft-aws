<?php

namespace codemonauts\aws;

use codemonauts\aws\models\Settings;
use codemonauts\aws\services\Assets;
use codemonauts\aws\services\Cloudfront;
use codemonauts\aws\services\S3;
use codemonauts\aws\variables\AwsVariables;
use craft\base\Plugin;
use Craft;
use craft\events\GetAssetThumbUrlEvent;
use yii\base\Event;
use craft\web\twig\variables\CraftVariable;

/**
 * Class Aws
 *
 * @property S3         $s3         The S3 component
 * @property Cloudfront $cloudfront The Cloudfront component
 * @property Assets     $assets     The assets component
 *
 * @package codemonauts\aws
 */
class Aws extends Plugin
{
    /**
     * @inheritDoc
     */
    public function init()
    {
        parent::init();

        // Add components
        $this->setComponents([
            'cloudfront' => Cloudfront::class,
            'assets' => Assets::class,
            's3' => S3::class,
        ]);

        if (Craft::$app->request->getIsConsoleRequest()) {
            $this->controllerNamespace = 'codemonauts\aws\console\controllers';
        }

        // Register asset thumb event if we should store and serve them from a bucket
        if (self::getInstance()->getSettings()->thumbnailsOnBucket) {
            Event::on(\craft\services\Assets::class, \craft\services\Assets::EVENT_GET_ASSET_THUMB_URL, function(GetAssetThumbUrlEvent $e) {
                $e->url = $this->assets->getThumbUrl(
                    $e->asset,
                    $e->width,
                    $e->height,
                    $e->generate
                );
            });
        }

        // Add variables to Twig
        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function(Event $e) {
            /** @var CraftVariable $variable */
            $variable = $e->sender;
            $variable->set('aws', AwsVariables::class);
        });
    }

    /**
     * @inheritdoc
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }
}
