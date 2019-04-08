<?php

namespace codemonauts\aws\services;

use codemonauts\aws\Aws;
use codemonauts\aws\traits\S3Trait;
use yii\base\Component;

class S3 extends Component
{
    use S3Trait;

    /**
     * @inheritdoc
     */
    public function init()
    {
        $config = Aws::getInstance()->getSettings();
        $this->key = $config->key;
        $this->secret = $config->secret;
        $this->region = $config->region;

        $this->client = $this->getClient();
    }
}
