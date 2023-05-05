<?php

namespace codemonauts\aws\components;

use Aws\S3\S3Client;
use codemonauts\aws\traits\S3Trait;
use Craft;
use craft\helpers\FileHelper;
use craft\web\Application;
use craft\web\AssetManager;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\caching\TagDependency;

class S3AssetManager extends AssetManager
{
    use S3Trait;

    public const CACHE_TAG = 'assetmanager';

    /**
     * @var S3Client The client from the SDK
     */
    private S3Client $client;

    /**
     * @var string The bucket to use for storing the resources.
     */
    public string $bucket;

    /**
     * @var string The region the bucket is located.
     */
    public string $region;

    /**
     * @var string The AWS key to authenticate with. Leave empty for instance roles.
     */
    public string $key;

    /**
     * @var string The AWS secret to authenticate with. Leave empty for instance roles.
     */
    public string $secret;

    /**
     * @var string An optional prefix for the base path.
     */
    public string $prefix;

    /**
     * @var string The base URL.
     */
    public string $url;

    /**
     * @var array published assets
     */
    private array $publishedOnBucket = [];

    private bool $dirtyList = false;

    /**
     * @inheritDoc
     */
    public function init(): void
    {
        $this->createClient();

        $generalConfig = Craft::$app->getConfig()->getGeneral();
        if ($generalConfig->buildId) {
            $this->basePath = trim($this->prefix, '/') . '/' . $generalConfig->buildId;
        } else {
            $this->basePath = trim($this->prefix, '/');
        }

        $this->baseUrl = $this->url . $this->basePath;

        $this->loadPublished();

        Craft::$app->on(Application::EVENT_AFTER_REQUEST, function() {
            if ($this->dirtyList) {
                $this->storePublished();
            }
        }, null, false);
    }

    /**
     * Stores the current list of published assets to the cache.
     */
    public function storePublished(): void
    {
        if (!$this->dirtyList) {
            Craft::info('Nothing changed.', 'S3AssetManager');
            return;
        }

        $cacheKey = self::CACHE_TAG . ':published:' . $this->basePath;
        $list = array_unique($this->publishedOnBucket);

        Craft::$app->getCache()->set(
            $cacheKey,
            $list,
            31536000,
            new TagDependency(['tags' => [self::CACHE_TAG]]),
        );

        Craft::info('Cache updated.', 'S3AssetManager');
    }

    /**
     * Loads all published resources of the current revision from the bucket.
     */
    protected function loadPublished(): void
    {
        $cacheKey = self::CACHE_TAG . ':published:' . $this->basePath;
        $list = Craft::$app->getCache()->get($cacheKey);

        if (!$list) {
            Craft::info('Cache not found.', 'S3AssetManager');
            $list = [];
            $results = $this->listObjects($this->bucket, $this->basePath);

            foreach ($results as $result) {
                if (isset($result['Contents'])) {
                    foreach ($result['Contents'] as $object) {
                        $key = substr($object['Key'], 0, strrpos($object['Key'], '/'));
                        $list[] = $key;
                        $list[] = $object['Key'];
                    }
                }
            }

            $list = array_unique($list);
            $this->dirtyList = true;
        }

        $this->publishedOnBucket = $list;
    }

    /**
     * @inheritdoc
     */
    protected function publishDirectory($src, $options): array
    {
        $dir = $this->hash($src);
        $dst = $this->basePath . '/' . $dir;

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
            Craft::info('Upload file "'.$dstFile.'"', 'S3AssetManager');
            $this->uploadFile($this->bucket, $dstFile, $src, [
                'ContentType' => $this->_getMimetype($src),
                'CacheControl' => 'max-age=31536000'
            ]);
            $this->publishedOnBucket[] = $dstFile;
            $this->publishedOnBucket[] = $dstDir;
            $this->dirtyList = true;
        }

        return [$dstFile, $this->baseUrl . '/' . $dir . '/' . $fileName];
    }

    /**
     * @inheritdoc
     */
    public function getPublishedUrl($path, bool $publish = false, ?string $filePath = null): string|false
    {
        if ($publish === true) {
            [, $url] = $this->publish($path);
        } else {
            $url = parent::getPublishedUrl($path);
        }

        if ($filePath !== null) {
            $url .= '/' . $filePath;
        }

        return $url;
    }

    /**
     * @inheritdoc
     */
    public function getAssetUrl($bundle, $asset, $appendTimestamp = null): string
    {
        return $this->getActualAssetUrl($bundle, $asset);
    }

    /**
     * Checks if the destination object already exists in the bucket.
     *
     * @param string $dst The destination object.
     *
     * @return bool
     */
    private function isPublished(string $dst): bool
    {
        return in_array($dst, $this->publishedOnBucket, true);
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
    private function uploadDirectory(string $src, string $dst, array $options): void
    {
        $handle = opendir($src);
        if ($handle === false) {
            throw new InvalidArgumentException("Unable to open directory: $src");
        }

        if ($this->isPublished($dst)) {
            return;
        }

        Craft::info('Destination not found in cache: '.$dst, 'S3AssetManager');

        while (($file = readdir($handle)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $from = $src . DIRECTORY_SEPARATOR . $file;
            $to = $dst . '/' . $file;
            $this->publishedOnBucket[] = $dst;

            if (is_file($from)) {
                if (!$this->isPublished($to)) {
                    Craft::info('Upload file "'.$to.'"', 'S3AssetManager');
                    $this->uploadFile($this->bucket, $to, $from, [
                        'ContentType' => $this->_getMimetype($from),
                        'CacheControl' => 'max-age=31536000',
                    ]);
                    $this->publishedOnBucket[] = $to;
                }
            } else if (!isset($options['recursive']) || $options['recursive']) {
                $this->uploadDirectory($from, $to, $options);
                $this->publishedOnBucket[] = $to;
            }
        }
        closedir($handle);

        $this->dirtyList = true;

        // If we are hit after request, we have to store the updated list for ourselves.
        if (Craft::$app->state === Application::STATE_SENDING_RESPONSE) {
            $this->storePublished();
        }
    }

    /**
     * Get mime type and fix bugs.
     *
     * @param string $src The file path.
     *
     * @return string The mime type.
     * @throws InvalidConfigException
     */
    private function _getMimetype(string $src): string
    {
        $mimeType = FileHelper::getMimeType($src);

        // Don't trust the following mime types
        if ($mimeType === 'text/x-java') {
            $mimeType = FileHelper::getMimeTypeByExtension($src) ?? $mimeType;
        }

        return (string)$mimeType;
    }
}
