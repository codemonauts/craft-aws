<?php

namespace codemonauts\aws\services;

use codemonauts\aws\Aws;
use craft\helpers\Image;
use yii\base\Component;
use craft\elements\Asset;
use craft\helpers\Assets as AssetsHelper;
use craft\helpers\FileHelper;
use Craft;

class Assets extends Component
{
    public function getThumbUrl(Asset $asset, int $width, int $height = null, bool $generate = false)
    {
        if (!Aws::getInstance()->getSettings()->thumbnailsEnabled) {
            return null;
        }

        $ext = $asset->getExtension();
        if (!Image::canManipulateAsImage($ext)) {
            return \Craft::$app->assets->getIconPath($asset);
        }

        if ($height === null) {
            $height = $width;
        }

        $ext = in_array($ext, Image::webSafeFormats(), true) ? $ext : 'jpg';
        $prefix = Aws::getInstance()->getSettings()->thumbnailsPrefix;
        $bucket = Aws::getInstance()->getSettings()->thumbnailsBucket;
        $baseUrl = Aws::getInstance()->getSettings()->thumbnailsBaseUrl;
        $key = $prefix . $asset->id . "/thumb-{$width}x{$height}.{$ext}";

        $meta = Aws::getInstance()->s3->getObjectInfo($bucket, $key);

        if ($meta === false) {
            $imageSource = Craft::$app->getAssetTransforms()->getLocalImageSource($asset);
            $svgSize = max($width, $height);
            $path = AssetsHelper::tempFilePath($ext);

            try {
                Craft::$app->getImages()->loadImage($imageSource, false, $svgSize)
                    ->scaleToFit($width, $height)
                    ->saveAs($path);
                Aws::getInstance()->s3->uploadFile($bucket, $key, $path, FileHelper::getMimeTypeByExtension('.' . $ext));
            } catch (ImageException $exception) {
                Craft::warning($exception->getMessage());

                return $this->getIconPath($asset);
            }
        }

        return $baseUrl . '/' . $key;
    }
}
