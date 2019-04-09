<?php

namespace codemonauts\aws\console\controllers;

use codemonauts\aws\Aws;
use Craft;
use codemonauts\aws\jobs\UpdateSearchIndex;
use craft\helpers\Console;
use yii\console\Controller;
use craft\db\Query;
use yii\console\ExitCode;
use yii\db\Exception;

class SearchController extends Controller
{
    public $defaultAction = 'updateIndex';

    /**
     * Dispatch jobs to update the search index for all elements.
     *
     * @throws Exception
     *
     * @return int
     */
    public function actionUpdateIndex(): int
    {
        if (Aws::getInstance()->getSettings()->queueMassUpdates === '') {
            $this->stderr('No queue for mass updates set!'.PHP_EOL, Console::FG_RED);
            return ExitCode::OK;
        }

        $queueComponent = Aws::getInstance()->getSettings()->queueMassUpdates;

        $queue = Craft::$app->$queueComponent;

        Craft::$app->getDb()->createCommand()
            ->truncateTable('{{%searchindex}}')
            ->execute();

        $elements = (new Query())
            ->select(['id', 'type'])
            ->from(['{{%elements}}'])
            ->all();

        $count = count($elements);
        $counter = 0;

        Console::startProgress($counter, $count, 'Dispatching jobs');

        foreach ($elements as $element) {
            $queue->push(new UpdateSearchIndex([
                'id' => $element['id'],
                'type' => $element['type'],
            ]));

            Console::updateProgress(++$counter, $count);
        }

        Console::endProgress(true, false);

        $this->stdout('Dispatched '.$count.' jobs.'.PHP_EOL, Console::FG_GREEN);

        return ExitCode::OK;
    }
}
