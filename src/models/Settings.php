<?php

namespace codemonauts\aws\models;

use craft\base\Model;

class Settings extends Model
{
    public bool $assetsOnBucket = false;
    public string $assetsBucket = '';
    public string $assetsRegion = '';
    public string $assetsKey = '';
    public string $assetsSecret = '';
    public string $assetsPrefix = '';
    public string $assetsBaseUrl = '';

    public string $cfKey = '';
    public string $cfSecret = '';
    public string $cfDefaultDistributionId = '';
}
