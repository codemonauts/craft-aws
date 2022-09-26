<?php

namespace codemonauts\aws\services;

use Aws\CloudFront\CloudFrontClient;
use Aws\Exception\AwsException;
use codemonauts\aws\Aws;
use Exception;
use yii\base\Component;
use Craft;
use yii\base\InvalidConfigException;

class Cloudfront extends Component
{
    /**
     * @var string The AWS access key to use, empty to use instance role
     */
    private string $key = '';

    /**
     * @var string The AWS secret
     */
    private string $secret = '';

    /**
     * @var string The default Cloudfront distribution ID to use.
     */
    private string $defaultDistributionId = '';

    /**
     * @inerhitDoc
     */
    public function init()
    {
        $this->key = Aws::$settings->cfKey;
        $this->secret = Aws::$settings->cfSecret;
        $this->defaultDistributionId = Aws::$settings->cfDefaultDistributionId;
    }

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

    /**
     * Returns the mobile operating system of the request
     *
     * @return string
     */
    public function getMobileOs(): string
    {
        if (isset($_SERVER['HTTP_CLOUDFRONT_IS_IOS_VIEWER']) && $_SERVER['HTTP_CLOUDFRONT_IS_IOS_VIEWER'] === 'true') {
            return 'iOS';
        }

        if (isset($_SERVER['HTTP_CLOUDFRONT_IS_ANDROID_VIEWER']) && $_SERVER['HTTP_CLOUDFRONT_IS_ANDROID_VIEWER'] === 'true') {
            return 'Android';
        }

        return 'Unknown';
    }

    /**
     * Creates an invalidation request for paths.
     *
     * @param array|string $paths Paths to invalidate.
     * @param string|null $distributionId Optional distribution ID to use. Otherwise, the default distribution ID from the settings is used.
     *
     * @return bool|string The ID of the invalidation or false on errors.
     * @throws \craft\errors\SiteNotFoundException
     * @throws \yii\base\InvalidConfigException
     */
    public function invalidate(array|string $paths, ?string $distributionId): bool|string
    {
        if (is_string($paths)) {
            $paths = [$paths];
        }

        if ($distributionId === null) {
            $distributionId = $this->defaultDistributionId;
        }

        if ($distributionId === '') {
            throw new InvalidConfigException('No Cloudfront distribution ID set.');
        }

        $callerReference = md5(Craft::$app->getSites()->getCurrentSite()->getBaseUrl() . microtime());

        $config = [
            'version' => 'latest',
            'region' => 'us-east-1',
        ];

        if (!empty($this->key)) {
            $config['credentials'] = [
                'key' => $this->key,
                'secret' => $this->secret,
            ];
        }

        $client = new CloudFrontClient($config);

        try {
            $result = $client->createInvalidation([
                'DistributionId' => $distributionId,
                'InvalidationBatch' => [
                    'CallerReference' => $callerReference,
                    'Paths' => [
                        'Items' => $paths,
                        'Quantity' => count($paths),
                    ],
                ],
            ]);

            if ($result['@metadata']['statusCode'] !== 201) {
                return false;
            }

            return $result['Invalidation']['Id'];
        } catch (AwsException $e) {
            throw new Exception($e->getAwsErrorMessage());
        }
    }
}
