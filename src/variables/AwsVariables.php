<?php

namespace codemonauts\aws\variables;

use codemonauts\aws\Aws;

class AwsVariables
{
    public function isMobileBrowser($tabletAsMobile = false)
    {
        return Aws::getInstance()->cloudfront->isMobileBrowser($tabletAsMobile);
    }
}
