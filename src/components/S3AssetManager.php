<?php

namespace codemonauts\aws\components;

use Aws\S3\S3Client;
use codemonauts\aws\traits\S3Trait;
use Craft;
use craft\helpers\FileHelper;
use craft\web\AssetManager;
use Yii;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;

class S3AssetManager extends AssetManager
{
    use S3Trait;

    /**
     * @var S3Client The client from the SDK
     */
    private $client;

    /**
     * @var string The real current revision of the resources.
     */
    public $currentRevision;

    /**
     * @var string The processed current revision of the resources.
     */
    public $revision;

    /**
     * @var string The bucket to use for storing the resources.
     */
    public $bucket;

    /**
     * @var string The region the bucket is located.
     */
    public $region;

    /**
     * @var string The AWS key to authenticate with. Leave empty for instance roles.
     */
    public $key;

    /**
     * @var string The AWS secret to authenticate with. Leave empty for instance roles.
     */
    public $secret;

    /**
     * @var string An optional prefix for the base path.
     */
    public $prefix;

    /**
     * @var string The base URL.
     */
    public $url;

    /**
     * @var array published assets
     */
    private $published = [];

    /**
     * @inheritDoc
     */
    public function init()
    {
        $this->client = $this->getClient();

        if (is_callable($this->revision)) {
            $this->currentRevision = $this->revision();
        } else {
            $this->currentRevision = $this->revision;
        }

        $this->basePath = $this->prefix . $this->currentRevision;
        $this->baseUrl = $this->url . $this->basePath;

        $this->loadPublished();
    }

    /**
     * Loads all published resources of the current revision from the bucket.
     */
    protected function loadPublished()
    {
        // $results = Aws::getInstance()->s3->listObjects($this->bucket, $this->basePath);

        $results = $this->listObjects($this->bucket, $this->basePath);

        $length = substr_count($this->basePath, '/') + 2;

        foreach ($results as $result) {
            if (isset($result['Contents'])) {
                foreach ($result['Contents'] as $object) {
                    $parts = explode('/', $object['Key']);
                    $hash = implode('/', array_slice($parts, 0, $length));
                    if (!in_array($hash, $this->published, true)) {
                        $this->published[] = $hash;
                    }
                    if (!in_array($object['Key'], $this->published, true)) {
                        $this->published[] = $object['Key'];
                    }
                }
            }
        }
    }

    /**
     * @inheritDoc
     */
    protected function hash($path)
    {
        if (is_callable($this->hashCallback)) {
            return call_user_func($this->hashCallback, $path);
        }

        $dir = is_file($path) ? dirname($path) : $path;
        $alias = Craft::alias($dir);

        return sprintf('%x', crc32($alias));
    }

    /**
     * @inheritDoc
     */
    public function getPublishedUrl($sourcePath, bool $publish = false, $filePath = null)
    {
        if ($publish === true) {
            list(, $url) = $this->publish($sourcePath);
        } else {
            $url = parent::getPublishedUrl($sourcePath);
        }

        if ($filePath !== null) {
            $url .= '/' . $filePath;
        }

        return $url;
    }

    /**
     * @inheritDoc
     */
    public function publish($path, $options = []): array
    {
        $path = Yii::getAlias($path);

        if (!is_string($path) || ($src = realpath($path)) === false) {
            throw new InvalidArgumentException("The file or directory to be published does not exist: $path");
        }

        if (is_file($src)) {
            return $this->publishFile($src);
        }

        return $this->publishDirectory($src, $options);
    }

    /**
     * @inheritdoc
     */
    protected function publishDirectory($src, $options): array
    {
        $dir = $this->hash($src);
        $dst = $this->basePath . DIRECTORY_SEPARATOR . $dir;

        if (!empty($options['forceCopy']) || ($this->forceCopy && !isset($options['forceCopy'])) || !$this->isPublished($dst)) {
            $this->uploadDirectory($src, $dst, $options);
        }

        return [$dst, $this->baseUrl . '/' . $dir];
    }

    /**
     * @inheritdoc
     */
    protected function publishFile($src): array
    {
        $dir = $this->hash($src);
        $fileName = basename($src);
        $dstDir = $this->basePath . '/' . $dir;
        $dstFile = $dstDir . '/' . $fileName;

        if (!$this->isPublished($dstFile)) {
            $this->uploadFile($this->bucket, $dstFile, $src, FileHelper::getMimeType($src));
        }

        return [$dstFile, $this->baseUrl . "/$dir/$fileName"];
    }

    /**
     * Checks if the destination object already exists in the bucket.
     *
     * @param string $dst The destination object.
     *
     * @return bool
     */
    protected function isPublished($dst): bool
    {
        return in_array($dst, $this->published, true);
    }

    /**
     * Uploads a directory recursively to the bucket.
     *
     * @param string $src Source directory.
     * @param string $dst Destination object in the bucket.
     * @param array $options Options for each upload.
     *
     * @throws InvalidConfigException
     */
    protected function uploadDirectory($src, $dst, $options)
    {
        $handle = opendir($src);
        if ($handle === false) {
            throw new InvalidArgumentException("Unable to open directory: $src");
        }

        while (($file = readdir($handle)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $from = $src . DIRECTORY_SEPARATOR . $file;
            $to = $dst . '/' . $file;

            if (isset($options['beforeCopy']) && !call_user_func($options['beforeCopy'], $from, $to)) {
                continue;
            }

            if (is_file($from)) {
                $this->uploadFile($this->bucket, $to, $from, FileHelper::getMimeType($from));
            } else if (!isset($options['recursive']) || $options['recursive']) {
                $this->uploadDirectory($from, $to, $options);
            }
            if (isset($options['afterCopy'])) {
                call_user_func($options['afterCopy'], $from, $to);
            }
        }
        closedir($handle);
    }
}
