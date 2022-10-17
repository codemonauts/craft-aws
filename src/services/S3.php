<?php

namespace codemonauts\aws\services;

use Aws\S3\S3Client;
use codemonauts\aws\traits\S3Trait;
use yii\base\Component;

class S3 extends Component
{
    use S3Trait;

    /**
     * @var S3Client The client from the SDK
     */
    private S3Client $client;

    /**
     * @var string The AWS access key to use, empty to use instance role
     */
    private string $key = '';

    /**
     * @var string The AWS secret
     */
    private string $secret = '';

    /**
     * @var string The AWS region to use for bucket operations
     */
    private string $region = 'us-east-1';

    /**
     * @inheritDoc
     */
    public function init()
    {
        $this->createClient();
    }

    /**
     * Sets the credentials and region for the S3 client.
     *
     * @param string|null $key
     * @param string|null $secret
     * @param string|null $region
     *
     * @return \codemonauts\aws\services\S3
     */
    public function setCredentials(?string $key = null, ?string $secret = null, ?string $region = null): self
    {
        if ($key !== null) {
            $this->key = $key;
        }

        if ($secret !== null) {
            $this->secret = $secret;
        }

        if ($region !== null) {
            $this->region = $region;
        }

        $this->createClient();

        return $this;
    }
}
