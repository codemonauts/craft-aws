<?php

namespace codemonauts\aws\services;

use codemonauts\aws\Aws;
use craft\errors\ImageException;
use craft\errors\VolumeException;
use craft\errors\VolumeObjectNotFoundException;
use craft\helpers\Image;
use yii\base\Component;
use craft\elements\Asset;
use craft\helpers\Assets as AssetsHelper;
use craft\helpers\FileHelper;
use Craft;
use yii\base\Exception;

class Assets extends Component
{
    /**
     * @var array To cache cache requests to te same asset.
     */
    private $thumbUrls = [];

    /**
     * @var array List of thumb sizes we have to create.
     */
    private $thumbSizes = [
        [30, 30],
        [60, 60],
        [100, 100],
        [200, 200],
        [380, 190],
        [760, 380],
    ];

    /**
     * Return the URL of a thumbnail and will generate all thumbs, if not in the cache.
     * The thumbnails will allways be generated if not in the cache, even if they already
     * exists on the S3 store.
     *
     * @param Asset $asset The asset to process.
     * @param int $width The width of the thumb.
     * @param int|null $height The height of the thumb, when null, it will be the same as $width
     * @param bool $generate If the thumb can be generated.
     *
     * @return string|null
     * @throws Exception
     * @throws VolumeException
     * @throws VolumeObjectNotFoundException
     */
    public function getThumbUrl(Asset $asset, int $width, int $height = null, bool $generate = false): ?string
    {
        if (isset($this->thumbUrls[$asset->id])) {
            return $this->thumbUrls[$asset->id]['baseUrl'] . "/thumb-{$width}x{$height}.{$this->thumbUrls[$asset->id]['ext']}";
        }

        if (($cachedMeta = Craft::$app->cache->get('aws-thumb-' . $asset->id)) !== false) {
            $this->thumbUrls[$asset->id] = $cachedMeta;

            return $cachedMeta['baseUrl'] . "/thumb-{$width}x{$height}.{$cachedMeta['ext']}";
        }

        $ext = $asset->getExtension();
        if (!Image::canManipulateAsImage($ext)) {
            return Craft::$app->assets->getIconPath($asset);
        }

        $baseUrl = Aws::getInstance()->getSettings()->thumbnailsBaseUrl;
        $meta = $this->createAllThumbSizes($asset);

        $this->thumbUrls[$asset->id] = [
            'baseUrl' => $baseUrl . $meta['path'],
            'ext' => $meta['ext'],
        ];

        Craft::$app->cache->set('aws-thumb-' . $asset->id, $this->thumbUrls[$asset->id], 0);

        return $baseUrl . $meta['path'] . "/thumb-{$width}x{$height}.{$meta['ext']}";
    }

    /**
     * Creates all necessary thumbs of the given asset.
     *
     * @param Asset $asset
     *
     * @return array
     * @throws VolumeException
     * @throws VolumeObjectNotFoundException
     * @throws Exception
     */
    public function createAllThumbSizes(Asset $asset): array
    {
        $ext = $asset->getExtension();
        $ext = in_array($ext, Image::webSafeFormats(), true) ? $ext : 'jpg';

        $prefix = Aws::getInstance()->getSettings()->thumbnailsPrefix;
        $bucket = Aws::getInstance()->getSettings()->thumbnailsBucket;

        $imageSource = Craft::$app->getAssetTransforms()->getLocalImageSource($asset);

        foreach ($this->thumbSizes as $size) {
            $key = $prefix . $asset->id . "/thumb-{$size[0]}x{$size[1]}.{$ext}";

            $svgSize = max($size[0], $size[1]);
            $path = AssetsHelper::tempFilePath($ext);

            try {
                Craft::$app->getImages()->loadImage($imageSource, false, $svgSize)
                    ->scaleToFit($size[0], $size[1])
                    ->saveAs($path);
                Aws::getInstance()->s3->uploadFile($bucket, $key, $path, FileHelper::getMimeTypeByExtension('.' . $ext));
                unlink($path);
            } catch (ImageException $exception) {
                Craft::warning($exception->getMessage());
            }
        }

        return [
            'path' => $prefix . $asset->id,
            'ext' => $ext
        ];
    }
}
