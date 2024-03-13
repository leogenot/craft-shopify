<?php

/**
 * Craft Shopify plugin for Craft CMS 4.x
 *
 * Bring Shopify products into Craft
 *
 * 
 */

namespace leogenot\craftshopify\utilities;

use DateTime;
use leogenot\craftshopify\CraftShopify;
use leogenot\craftshopify\assetbundles\craftshopifyutilityutility\CraftShopifyUtilityUtilityAsset;

use Craft;
use craft\base\Utility;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use utils\Str;
use craft\base\Field;
// use craft\base\Fields;
use craft\base\FieldInterface;
use craft\fields\MissingField;
use craft\fields\PlainText;
use craft\redactor\Field as Redactor;
use craft\helpers\ArrayHelper;
use craft\helpers\UrlHelper;
// use craft\models\FieldGroup;
use craft\web\Controller;
use craft\models\FieldGroup;
use craft\models\FieldGroup as FieldGroupModel;
use craft\services\Fields;
use yii\db\Connection;


/**
 * Craft Shopify Utility
 *

 */
class CraftShopifyUtility extends Utility {
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string {
        return Craft::t('craft-shopify', 'Craft Shopify');
    }

    /**
     * @inheritdoc
     */
    public static function id(): string {
        return 'craft-shopify';
    }

    /**
     * @inheritdoc
     */
    public static function iconPath(): string {
        return Craft::getAlias("@leogenot/craftshopify/assetbundles/craftshopifyutilityutility/dist/img/CraftShopifyUtility-icon.svg");
    }

    /**
     * @inheritdoc
     */
    public static function badgeCount(): int {
        return 0;
    }

    /**
     * @inheritdoc
     */
    public static function contentHtml(): string {
        Craft::$app->getView()->registerAssetBundle(CraftShopifyUtilityUtilityAsset::class);

        return Craft::$app->getView()->renderTemplate('craft-shopify/_components/utilities/CraftShopify');
    }
}
