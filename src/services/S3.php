<?php

namespace codemonauts\aws\services;

use Aws\S3\S3Client;
use codemonauts\aws\Aws;
use codemonauts\aws\traits\S3Trait;
use yii\base\Component;

class S3 extends Component
{
    use S3Trait;

    /**
     * @var S3Client The client from the SDK
     */
    private $client;

    /**
     * @var string The AWS access key to use, empty to use instance role
     */
    private $key = '';

    /**
     * @var string The AWS secret
     */
    private $secret = '';

    /**
     * @var string The AWS region to use for bucket operations
     */
    private $region = '';

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
