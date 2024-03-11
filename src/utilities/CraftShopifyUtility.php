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

    public static function createSection($sectionName): void {

        $section = new Section([
            'name' => $sectionName,
            'handle' => Str::to_camel($sectionName),
            'type' => Section::TYPE_CHANNEL,
            'siteSettings' => [
                new Section_SiteSettings([
                    'siteId' => Craft::$app->sites->getPrimarySite()->id,
                    'enabledByDefault' => true,
                    'hasUrls' => true,
                    'uriFormat' => Str::to_camel($sectionName) . '/{slug}',
                    'template' => Str::to_camel($sectionName) . '/_entry',
                ]),
            ]
        ]);
        Craft::$app->sections->saveSection($section);
    }

    public static function getFieldGroup($groupName) {
        $groups = Craft::$app->fields->getAllGroups();
        foreach ($groups as $group) {
            if ($group->name == $groupName) {
                return $group->id;
            }
        }

        $group = new FieldGroup();
        $group->name = $groupName;
        Craft::$app->getFields()->saveGroup($group);

        return $group->id;
    }


    public static function createFields($groupName): void {
        $fieldsService = Craft::$app->getFields();
        $sectionsService = Craft::$app->getSections();

        $groupId = self::getFieldGroup($groupName);


        // Create Shopify ID field
        $shopifyId = $fieldsService->createField([
            'type' => PlainText::class,
            'groupId' => $groupId,
            'name' => 'Shopify Id',
            'handle' => 'shopifyId',
            'instructions' => 'Enter the Shopify ID here.',
            'settings' => [
                'placeholder' => 'Shopify ID',
            ],
        ]);

        // Create Product Description Redactor field
        $productDescription = $fieldsService->createField([
            'type' => Redactor::class,
            'groupId' => $groupId,
            'name' => 'Product Description',
            'handle' => 'productDescription',
            'instructions' => 'Enter the product description here.',
            'settings' => [],
        ]);

        // Save fields
        $fields = [$shopifyId, $productDescription];
        foreach ($fields as $field) {
            // $fieldsService->saveField($field);
            if (!$fieldsService->saveField($field)) {
                throw new \Exception('Failed to save field: ' . $field->name);
            }
        }

        // Get the product section
        $productSection = $sectionsService->getSectionByHandle($groupName);

        if ($productSection) {
            foreach ($fields as $field) {
                if (!$productSection->fieldLayout->addField($field)) {
                    throw new \Exception('Failed to save entry type: ' . $field->name);
                }
            }
        }




        // Output success message
        echo 'Fields created successfully.';
    }
    public static function cleanPlugin($sectionName): void {
        // Get the Section by handle
        $section = Craft::$app->sections->getSectionByHandle(Str::to_camel($sectionName));

        if ($section) {

            $elementIds = Craft::$app->db->createCommand('
            SELECT id
            FROM entries
            WHERE sectionId = :sectionId
            ')->bindValues([':sectionId' => $section->id])->queryColumn();

            $rowsAffected = Craft::$app->db->createCommand('
            DELETE FROM elements
            WHERE id IN (
                SELECT id
                FROM entries
                WHERE sectionId = :sectionId
            )
            ')->bindValues([':sectionId' => $section->id])->execute();

            Craft::$app->sections->deleteSectionById($section->id);
        }

        // Get the FieldGroup by name
        $fieldGroup = self::getFieldGroup($sectionName);

        // If field group exists, delete it
        if ($fieldGroup) {
            if (!Craft::$app->fields->deleteGroupById($fieldGroup)) {
                throw new \Exception('Failed to delete field group: ' . $fieldGroup->name);
            }
        }
    }
}
