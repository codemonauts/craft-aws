<?php

namespace codemonauts\aws\variables;

use codemonauts\aws\Aws;

class AwsVariables
{
    public function isMobileBrowser(bool $tabletAsMobile = false): bool
    {
        return Aws::$plugin->cloudfront->isMobileBrowser($tabletAsMobile);
    }

    public function getBrowserType(): string
    {
        return Aws::$plugin->cloudfront->getBrowserType();
    }

    public function getMobileOs(): string
    {
        return Aws::$plugin->cloudfront->getMobileOs();
    }
}
