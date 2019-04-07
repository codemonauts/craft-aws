<?php

namespace codemonauts\aws\models;

use craft\base\Model;

class Settings extends Model
{
    public $cpresourcesRevision = '';
    public $cpresourcesBucket = '';
    public $cpresourcesPrefix = '';
    public $cpresourcesBaseUrl = '';

    public $key = '';
    public $secret = '';
    public $region = '';

    public $thumbnailsEnabled = false;
    public $thumbnailsBucket = '';
    public $thumbnailsPrefix = '';
    public $thumbnailsBaseUrl = '';

}
