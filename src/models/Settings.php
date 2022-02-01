<?php

namespace codemonauts\aws\models;

use craft\base\Model;

class Settings extends Model
{
    public $key = '';
    public $secret = '';
    public $region = '';

    public $resourceRevision = '';
    public $resourceBucket = '';
    public $resourcePrefix = '';
    public $resourceBaseUrl = '';

    public $thumbnailsOnBucket = false;
    public $thumbnailsBucket = '';
    public $thumbnailsPrefix = '';
    public $thumbnailsBaseUrl = '';

    public $queueMassUpdates = '';
}
