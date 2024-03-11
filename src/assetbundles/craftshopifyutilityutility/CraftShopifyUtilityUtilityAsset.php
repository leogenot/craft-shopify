<?php

/**
 * Craft Shopify plugin for Craft CMS 4.x
 *
 * Bring Shopify products into Craft
 *
 * 
 * 
 */

namespace leogenot\craftshopify\assetbundles\craftshopifyutilityutility;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;


class CraftShopifyUtilityUtilityAsset extends AssetBundle {
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init() {
        $this->sourcePath = "@leogenot/craftshopify/assetbundles/craftshopifyutilityutility/dist";

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'js/CraftShopifyUtility.js',
        ];

        $this->css = [
            'css/CraftShopifyUtility.css',
        ];

        parent::init();
    }
}
