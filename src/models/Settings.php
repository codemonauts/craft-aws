<?php

namespace codemonauts\aws\models;

use craft\base\Model;

class Settings extends Model
{
    public $key = '';
    public $secret = '';
    public $region = '';

    public $resourcesOnBucket = false;
    public $thumbnailsOnBucket = false;

    public $resourceRevision = '';
    public $resourceBucket = '';
    public $resourcePrefix = '';
    public $resourceBaseUrl = '';

    public $thumbnailsBucket = '';
    public $thumbnailsPrefix = '';
    public $thumbnailsBaseUrl = '';

}
