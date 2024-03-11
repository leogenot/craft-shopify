<?php

/**
 * craft-shopify-cms module for Craft CMS 4.x
 *

 * 
 */


namespace leogenot\craftshopify\records;


use craft\db\ActiveRecord;
use craft\helpers\Json;

/**
 *
 * @property string $payload
 * @property string $errors
 * @property string $type
 * @property string $webhookId
 */
class WebhookResponseRecord extends ActiveRecord {
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return '{{%shopifywebhooks}}';
    }

    /**
     * Get the JSON payload
     *
     * @return mixed|null
     */
    public function getPayload() {
        return Json::decode($this->payload);
    }
}
