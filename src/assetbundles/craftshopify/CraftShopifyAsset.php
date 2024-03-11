<?php

/**
 * Craft Shopify plugin for Craft CMS 4.x
 *
 * Bring Shopify products into Craft
 *
 * 
 * 
 */

namespace leogenot\craftshopify\assetbundles\craftshopify;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;


class CraftShopifyAsset extends AssetBundle {
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init() {
        $this->sourcePath = "@leogenot/craftshopify/assetbundles/craftshopify/dist";

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'js/CraftShopify.js',
        ];

        $this->css = [
            'css/CraftShopify.css',
        ];

        parent::init();
    }
}
