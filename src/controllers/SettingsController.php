<?php

/**
 * craft-shopify module for Craft CMS 4.x
 *

 * 
 */


namespace leogenot\craftshopify\controllers;


use craft\web\Controller;
use leogenot\craftshopify\CraftShopify;
use leogenot\craftshopify\models\Settings;
use yii\web\Response;


class SettingsController extends Controller {

    /**
     * Render the Shopify settings route
     *
     * @param Settings|null $settings
     * @return Response
     */
    public function actionShopify(Settings $settings = null): Response {
        if ($settings === null) {
            $settings = CraftShopify::$plugin->getSettings();
        }

        return $this->renderTemplate('craft-shopify/settings/shopify', [
            'settings' => $settings,
            'plugin' => CraftShopify::getInstance()
        ]);
    }

    public function actionTemplates(Settings $settings = null) {
        if ($settings === null) {
            $settings = CraftShopify::$plugin->getSettings();
        }

        return $this->renderTemplate('craft-shopify/settings/templates', [
            'settings' => $settings,
            'plugin' => CraftShopify::getInstance()
        ]);
    }

    /**
     * Render the field layout settings route
     *
     * @param Settings|null $settings
     * @return Response
     */
    public function actionFieldLayouts(Settings $settings = null): Response {
        if ($settings === null) {
            $settings = CraftShopify::$plugin->getSettings();
        }

        return $this->renderTemplate('craft-shopify/settings/field-layouts', [
            'settings' => $settings
        ]);
    }
}
