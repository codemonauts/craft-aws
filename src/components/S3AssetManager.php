<?php

namespace codemonauts\aws\components;

use codemonauts\aws\Aws;
use Craft;
use craft\db\Table;
use craft\errors\DbConnectException;
use craft\helpers\FileHelper;
use craft\web\AssetManager;
use yii\db\Exception as DbException;

class S3AssetManager extends AssetManager
{
    private $currentRevision;

    public function init()
    {
        parent::init();

        $revision = Aws::getInstance()->getSettings()->assetRevision;

        if (is_string($revision)) {
            $this->currentRevision = Aws::getInstance()->getSettings()->assetRevision;
        } elseif (is_callable($revision)) {
            $this->currentRevision = call_user_func($revision);
        }
    }

    protected function hash($path)
    {
        $dir = is_file($path) ? dirname($path) : $path;
        $alias = Craft::alias($dir);
        $hash = sprintf('%x', crc32($alias));

        try {
            Craft::$app->getDb()->createCommand()
                ->upsert(Table::RESOURCEPATHS, [
                    'hash' => $hash,
                ], [
                    'path' => $alias,
                ], [], false)
                ->execute();
        } catch (\Exception $e) {
            Craft::error($e->getMessage(), __METHOD__);
        }

        $hash = $this->currentRevision . DIRECTORY_SEPARATOR . $hash;

        return $hash;
    }
}
