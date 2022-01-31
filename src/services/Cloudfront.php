<?php

namespace codemonauts\aws\services;

use yii\base\Component;
use Craft;

class Cloudfront extends Component
{
    /**
     * Returns whether the requests came from Cloudfront
     *
     * @return bool
     */
    public function isCloudfrontRequest(): bool
    {
        return $this->getRequestId() !== null;
    }

    /**
     * Returns the Trace ID from a load balancer
     *
     * @return string|null
     */
    public function getTraceId(): ?string
    {
        return $_SERVER['HTTP_X_AMZN_TRACE_ID'] ?? null;
    }

    /**
     * Returns the Cloudfront unique request ID
     *
     * @return string|null
     */
    public function getRequestId(): ?string
    {
        return $_SERVER['HTTP_X_AMZ_CF_ID'] ?? null;
    }

    /**
     * Return whether the request is from a mobile device.
     *
     * @param bool $tabletAsMobile
     *
     * @return bool
     */
    public function isMobileBrowser(bool $tabletAsMobile = false): bool
    {
        if (isset($_SERVER['HTTP_CLOUDFRONT_IS_DESKTOP_VIEWER']) && $_SERVER['HTTP_CLOUDFRONT_IS_DESKTOP_VIEWER'] === 'true') {
            return false;
        }

        if (isset($_SERVER['HTTP_CLOUDFRONT_IS_TABLET_VIEWER']) && $_SERVER['HTTP_CLOUDFRONT_IS_TABLET_VIEWER'] === 'true') {
            return $tabletAsMobile;
        }

        if (isset($_SERVER['HTTP_CLOUDFRONT_IS_MOBILE_VIEWER']) && $_SERVER['HTTP_CLOUDFRONT_IS_MOBILE_VIEWER'] === 'true') {
            return true;
        }

        return Craft::$app->request->isMobileBrowser($tabletAsMobile);
    }

    /**
     * Returns the device type of the request
     *
     * @return string
     */
    public function getBrowserType(): string
    {
        if (isset($_SERVER['HTTP_CLOUDFRONT_IS_SMARTTV_VIEWER']) && $_SERVER['HTTP_CLOUDFRONT_IS_SMARTTV_VIEWER'] === 'true') {
            return 'SmartTV';
        }

        if (isset($_SERVER['HTTP_CLOUDFRONT_IS_DESKTOP_VIEWER']) && $_SERVER['HTTP_CLOUDFRONT_IS_DESKTOP_VIEWER'] === 'true') {
            return 'Desktop';
        }

        if (isset($_SERVER['HTTP_CLOUDFRONT_IS_TABLET_VIEWER']) && $_SERVER['HTTP_CLOUDFRONT_IS_TABLET_VIEWER'] === 'true') {
            return 'Tablet';
        }

        if (isset($_SERVER['HTTP_CLOUDFRONT_IS_MOBILE_VIEWER']) && $_SERVER['HTTP_CLOUDFRONT_IS_MOBILE_VIEWER'] === 'true') {
            return 'Mobile';
        }

        return 'Unknown';
    }
}
