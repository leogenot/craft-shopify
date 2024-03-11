<?php

/**
 * Craft Shopify plugin for Craft CMS 4.x
 *
 * Bring Shopify products into Craft
 *
 * 
 * 
 */

namespace leogenot\craftshopify\models;

use craft\base\Model;
use craft\validators\TemplateValidator;


class Settings extends Model {
    /**
     * @var string
     */
    public string $apiKey = '$SHOPIFY_API_KEY';


    /**
     * @var string
     */
    public string $apiPassword = '$SHOPIFY_ADMIN_ACCESS_TOKEN';

    /**
     * @var string
     */
    public string $hostname = '$SHOPIFY_HOSTNAME';

    /**
     * @var string
     */
    public string $webhookSecret = '$SHOPIFY_WEBHOOK_SECRET';

    /**
     * @var string
     */
    public $sectionOptions;

    /**
     * @var string
     */
    public $previewPath;


    /**
     * @inheritdoc
     */
    public function rules(): array {
        return [
            [['apiKey', 'apiPassword', 'hostname', 'webhookSecret'], 'string'],
            [
                ['apiKey', 'apiPassword', 'hostname', 'required']
            ]
        ];
    }
}
