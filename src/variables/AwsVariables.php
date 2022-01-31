<?php

namespace codemonauts\aws\variables;

use codemonauts\aws\Aws;

class AwsVariables
{
    public function isMobileBrowser(bool $tabletAsMobile = false): bool
    {
        return Aws::getInstance()->cloudfront->isMobileBrowser($tabletAsMobile);
    }

    public function getBrowserType(): string
    {
        return Aws::getInstance()->cloudfront->getBrowserType();
    }
}
