<?php

namespace codemonauts\aws\jobs;

use Craft;
use craft\queue\BaseJob;
use craft\base\ElementInterface;
use craft\base\Element;
use craft\base\Field;

class UpdateSearchIndex extends BaseJob
{
    public $id;

    public $type;

    /**
     * @inheritDoc
     */
    public function execute($queue): bool
    {
        $this->setProgress($queue, 1);

        /** @var ElementInterface $class */
        $class = $this->type;

        if ($class::isLocalized()) {
            $siteIds = Craft::$app->getSites()->getAllSiteIds();
        } else {
            $siteIds = [Craft::$app->getSites()->getPrimarySite()->id];
        }

        $query = $class::find()
            ->id($this->id)
            ->anyStatus();

        foreach ($siteIds as $siteId) {
            $query->siteId($siteId);
            $element = $query->one();

            if ($element) {
                /** @var Element $element */
                Craft::$app->getSearch()->indexElementAttributes($element);

                if ($class::hasContent() && ($fieldLayout = $element->getFieldLayout()) !== null) {
                    $keywords = [];

                    foreach ($fieldLayout->getFields() as $field) {
                        /** @var Field $field */
                        // Set the keywords for the content's site
                        $fieldValue = $element->getFieldValue($field->handle);
                        $fieldSearchKeywords = $field->getSearchKeywords($fieldValue, $element);
                        $keywords[$field->id] = $fieldSearchKeywords;
                    }

                    Craft::$app->getSearch()->indexElementFields($element->id, $siteId, $keywords);
                }
            }
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    protected function defaultDescription()
    {
        return 'Update search index of element';
    }
}
