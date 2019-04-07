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
    public function isCloudfrontRequest()
    {
        return $this->getRequestId() !== null ? true : false;
    }

    /**
     * Returns the Trace ID from a load balancer
     *
     * @return string|null
     */
    public function getTraceId()
    {
        if (isset($_SERVER['HTTP_X_AMZN_TRACE_ID'])) {
            return $_SERVER['HTTP_X_AMZN_TRACE_ID'];
        }

        return null;
    }

    /**
     * Returns the Cloudfront unique request ID
     *
     * @return string|null
     */
    public function getRequestId()
    {
        if (isset($_SERVER['HTTP_X_AMZ_CF_ID'])) {
            return $_SERVER['HTTP_X_AMZ_CF_ID'];
        }

        return null;
    }

    /**
     * Return whether the request is from a mobile device.
     *
     * @param bool $tabletAsMobile
     *
     * @return bool
     */
    public function isMobileBrowser($tabletAsMobile = false)
    {
        if (isset($_SERVER['HTTP_CLOUDFRONT_IS_TABLET_VIEWER']) && $_SERVER['HTTP_CLOUDFRONT_IS_TABLET_VIEWER'] == 'true') {
            return $tabletAsMobile ? true : false;
        } elseif (isset($_SERVER['HTTP_CLOUDFRONT_IS_MOBILE_VIEWER']) && $_SERVER['HTTP_CLOUDFRONT_IS_MOBILE_VIEWER'] == 'true') {
            return true;
        } elseif (isset($_SERVER['HTTP_CLOUDFRONT_IS_DESKTOP_VIEWER']) && $_SERVER['HTTP_CLOUDFRONT_IS_DESKTOP_VIEWER'] == 'true') {
            return false;
        } else {
            return Craft::$app->request->isMobileBrowser($tabletAsMobile);
        }
    }

    /**
     * Returns the device type of the request
     *
     * @return string
     */
    public function getBrowserType()
    {
        if (isset($_SERVER['HTTP_CLOUDFRONT_IS_SMARTTV_VIEWER']) && $_SERVER['HTTP_CLOUDFRONT_IS_SMARTTV_VIEWER'] == 'true') {
            return 'SmartTV';
        } elseif (isset($_SERVER['HTTP_CLOUDFRONT_IS_DESKTOP_VIEWER']) && $_SERVER['HTTP_CLOUDFRONT_IS_DESKTOP_VIEWER'] == 'true') {
            return 'Desktop';
        } elseif (isset($_SERVER['HTTP_CLOUDFRONT_IS_TABLET_VIEWER']) && $_SERVER['HTTP_CLOUDFRONT_IS_TABLET_VIEWER'] == 'true') {
            return 'Tablet';
        } elseif (isset($_SERVER['HTTP_CLOUDFRONT_IS_MOBILE_VIEWER']) && $_SERVER['HTTP_CLOUDFRONT_IS_MOBILE_VIEWER'] == 'true') {
            return 'Mobile';
        }

        return 'Unknown';
    }
}
