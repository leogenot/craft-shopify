<?php

/**
 * slumberkins-cms module for Craft CMS 4.x
 *

 * 
 */

namespace leogenot\craftshopify\console\controllers;

use craft\console\Controller;
use leogenot\craftshopify\CraftShopify;
use yii\console\ExitCode;



class WebhookController extends Controller {
    public $defaultAction = 'purge';

    /**
     * Length in days to purge before
     *
     * @var int
     */
    public $olderThan = 30;

    /**
     * @param string $actionID
     * @return array
     */
    public function options($actionID): array {
        $options = parent::options($actionID);
        $options[] = 'olderThan';

        return $options;
    }

    /**
     * Purge webhook records
     */
    public function actionPurge() {
        CraftShopify::$plugin->webhook->purgeResponses($this->olderThan);
        return ExitCode::OK;
    }
}
