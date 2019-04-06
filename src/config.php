<?php

return [
    'assetRevision' => function() {
        return trim(file_get_contents(Craft::getAlias('@root/build.txt')));
    },
];
