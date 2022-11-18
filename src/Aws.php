<?php

namespace codemonauts\aws;

use codemonauts\aws\components\S3AssetManager;
use codemonauts\aws\models\Settings;
use codemonauts\aws\services\Cloudfront;
use codemonauts\aws\services\S3;
use codemonauts\aws\variables\AwsVariables;
use craft\base\Plugin;
use Craft;
use craft\helpers\App;
use yii\base\Event;
use craft\web\twig\variables\CraftVariable;

/**
 * @property S3 $s3 The S3 component
 * @property Cloudfront $cloudfront The Cloudfront component
 */
class Aws extends Plugin
{
    /**
     * @var \codemonauts\aws\Aws
     */
    public static Aws $plugin;

    /**
     * @var \codemonauts\aws\models\Settings|null
     */
    public static ?Settings $settings;

    /**
     * @inheritDoc
     */
    public bool $hasCpSettings = true;

    /**
     * @inheritDoc
     */
    public function init(): void
    {
        parent::init();

        self::$plugin = $this;

        self::$settings = self::$plugin->getSettings();

        // Add components
        $this->setComponents([
            'cloudfront' => Cloudfront::class,
            's3' => S3::class,
        ]);

        // Register asset manager if enabled
        if (App::parseBooleanEnv(self::$settings->assetsOnBucket)) {
            $componentConfig = [
                'assetManager' => [
                    'class' => S3AssetManager::class,
                    'bucket' => App::parseEnv(self::$settings->assetsBucket),
                    'region' => App::parseEnv(self::$settings->assetsRegion),
                    'key' => App::parseEnv(self::$settings->assetsKey),
                    'secret' => App::parseEnv(self::$settings->assetsSecret),
                    'prefix' => App::parseEnv(self::$settings->assetsPrefix),
                    'url' => App::parseEnv(self::$settings->assetsBaseUrl),
                ],
            ];
            Craft::$app->setComponents($componentConfig);
        }

        // Add variables to Twig
        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function (Event $event) {
            /** @var CraftVariable $variable */
            $variable = $event->sender;
            $variable->set('aws', AwsVariables::class);
        });
    }

    /**
     * @inheritdoc
     */
    protected function createSettingsModel(): Settings
    {
        return new Settings();
    }

    /**
     * @inheritDoc
     */
    protected function settingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('aws/settings', [
                'settings' => $this->getSettings(),
            ]
        );
    }
}
