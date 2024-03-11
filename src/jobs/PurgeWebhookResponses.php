<?php

/**
 * slumberkins-cms module for Craft CMS 4.x
 *

 * 
 */


namespace leogenot\craftshopify\jobs;


use Craft;
use craft\queue\BaseJob;
use DateTime;
use Exception;
use leogenot\craftshopify\records\WebhookResponseRecord;


class PurgeWebhookResponses extends BaseJob {

    /**
     * @var int Purge records older than X days
     */
    public $olderThan;

    /**
     * @inheritDoc
     * @param $queue
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function execute($queue): void {
        if (!$this->olderThan) {
            throw new Exception('olderThan is required');
        }

        $now = new DateTime();
        $lowerBound = $now->modify('-' . $this->olderThan . ' day');
        $lowerBoundString = $lowerBound->format('Y-m-d');

        $query = WebhookResponseRecord::find()
            ->where(['<', 'dateCreated', $lowerBoundString]);

        $totalRecords = $query->count();

        /** @var WebhookResponseRecord $record */
        foreach ($query->each() as $i => $record) {
            $this->setProgress(
                $queue,
                $i / $totalRecords,
                Craft::t('craft-shopify', '{step, number} of {total, number}', [
                    'step' => $i + 1,
                    'total' => $totalRecords
                ])
            );

            $record->delete();
        }
    }

    protected function defaultDescription(): string {
        return 'Purging webhook responses';
    }
}
