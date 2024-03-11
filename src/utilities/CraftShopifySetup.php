<?php

namespace leogenot\craftshopify\utilities;


use Craft;
use craft\base\Element;
use craft\base\Field;
use craft\db\Migration;
use craft\elements\Entry;
use craft\errors\ElementNotFoundException;
use craft\errors\EntryTypeNotFoundException;
use craft\errors\SectionNotFoundException;
use craft\errors\SiteNotFoundException;
use craft\fieldlayoutelements\CustomField;
use craft\fields\PlainText;
use craft\redactor\Field as Redactor;
use craft\models\EntryType;
use craft\models\FieldGroup;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\db\Schema;
use nystudio107\codefield;
use nystudio107\codefield\fields\Code;

/**
 * CraftShopifySetup migration.
 */
class CraftShopifySetup extends Migration {
    private const SECTION_CONFIGS = [
        [
            'class' => Section::class,
            'name' => 'Products',
            'handle' => 'products',
            'type' => Section::TYPE_CHANNEL,
        ],
    ];
    private const FIELD_GROUP_CONFIGS = [
        [
            'class' => FieldGroup::class,
            'name' => 'Products',
        ],
    ];
    private const FIELD_CONFIGS = [
        [
            'class' => PlainText::class,
            'name' => 'Shopify Id',
            'handle' => 'shopifyId',
            'instructions' => 'Enter the Shopify ID here.',
        ],
        [
            'class' => Redactor::class,
            'name' => 'Product description',
            'handle' => 'productDescription',
            'instructions' => 'Enter the product description here.',
        ],
        [
            'class' => Code::class,
            'name' => 'JSON Data',
            'handle' => 'productJsonData',
        ],
    ];

    private const ENTRY_TYPE_HANDLE = 'default';

    /**
     * @inheritdoc
     */
    public function safeUp(): bool {
        try {
            $this->createShopifyProductsTable();
            $this->createSections(self::SECTION_CONFIGS);
            $this->createFieldGroups(self::FIELD_GROUP_CONFIGS);
            $this->createFields(self::FIELD_CONFIGS, self::FIELD_GROUP_CONFIGS[0]['name']);
            $this->addFieldsToSection(self::FIELD_CONFIGS, self::SECTION_CONFIGS[0]['handle'], self::ENTRY_TYPE_HANDLE);
        } catch (\Throwable $e) {
            throw $e;
            return false;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool {
        try {
            $this->deleteEntryData(self::SECTION_CONFIGS[0]['handle']);
            $this->removeFieldsFromSection(self::FIELD_CONFIGS, self::SECTION_CONFIGS[0]['handle'], self::ENTRY_TYPE_HANDLE);
            $this->deleteFields(self::FIELD_CONFIGS);
            $this->deleteFieldGroups(self::FIELD_GROUP_CONFIGS);
            $this->deleteSections(self::SECTION_CONFIGS);
            $this->removeShopifyProductsTable();
        } catch (\Throwable $e) {
            throw $e;
            return false;
        }

        return true;
    }

    /**
     * Create Sections based on the $sectionConfigs
     *
     * @param array $sectionConfigs
     * @return void
     * @throws \Throwable
     * @throws SectionNotFoundException
     * @throws SiteNotFoundException
     */
    protected function createSections(array $sectionConfigs): void {
        foreach ($sectionConfigs as $sectionConfig) {
            $handle = $sectionConfig['handle'];
            if (Craft::$app->sections->getSectionByHandle($handle)) {
                echo "Section {$handle} already exists" . PHP_EOL;
                continue;
            }
            $section = Craft::createObject(array_merge($sectionConfig, [
                'siteSettings' => [
                    new Section_SiteSettings([
                        'siteId' => Craft::$app->sites->getPrimarySite()->id,
                        'enabledByDefault' => true,
                        'hasUrls' => true,
                        'uriFormat' => "{$handle}/{slug}",
                        'template' => "{$handle}/_entry",
                    ]),
                ]
            ]));
            if (!Craft::$app->sections->saveSection($section)) {
                echo "Section {$handle} could not be saved" . PHP_EOL;
            }
        }
    }
    /**
     * Create Products DB
     *
     * @param array $sectionConfigs
     * @return void
     * @throws \Throwable
     * @throws SectionNotFoundException
     * @throws SiteNotFoundException
     */
    protected function createShopifyProductsTable(): void {
        $db = Craft::$app->getDb();
        $tableName = 'shopifyproducts';

        if (!$db->tableExists($tableName)) {
            $db->createCommand('CREATE TABLE `shopifyproducts` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `bodyHtml` mediumtext,
            `bodyHtmlMetafieldId` bigint DEFAULT NULL,
            `shopifyId` bigint DEFAULT NULL,
            `jsonData` mediumtext,
            `productType` text,
            `dateCreated` datetime DEFAULT NULL,
            `dateUpdated` datetime DEFAULT NULL,
            `uid` char(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
            PRIMARY KEY (`id`)
            ) ENGINE=InnoDB AUTO_INCREMENT=19723 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;')->execute();
        }
    }
    /**
     * Remove Products DB
     *
     * @param array $sectionConfigs
     * @return void
     * @throws \Throwable
     * @throws SectionNotFoundException
     * @throws SiteNotFoundException
     */
    protected function removeShopifyProductsTable(): void {
        $db = Craft::$app->getDb();
        $tableName = 'shopifyproducts';

        if ($db->tableExists($tableName)) {
            $db->createCommand('DROP TABLE `shopifyproducts`')->execute();
        }
    }

    /**
     * Delete Sections based on the $sectionConfigs
     *
     * @param array $sectionConfigs
     * @return void
     * @throws \Throwable
     */
    protected function deleteSections(array $sectionConfigs): void {
        foreach ($sectionConfigs as $sectionConfig) {
            $handle = $sectionConfig['handle'];
            $section = Craft::$app->sections->getSectionByHandle($handle);
            if ($section) {
                Craft::$app->sections->deleteSection($section);
            }
        }
    }

    /**
     * Create FieldGroups based on the $fieldGroupConfigs
     *
     * @param array $fieldGroupConfigs
     * @return void
     * @throws InvalidConfigException
     */
    protected function createFieldGroups(array $fieldGroupConfigs): void {
        foreach ($fieldGroupConfigs as $fieldGroupConfig) {
            $name = $fieldGroupConfig['name'];
            if ($this->getFieldGroupByName($name)) {
                echo "Group {$name} already exists" . PHP_EOL;
                continue;
            }
            $group = Craft::createObject($fieldGroupConfig);
            Craft::$app->getFields()->saveGroup($group);
        }
    }

    /**
     * Delete FieldGroups based on the $fieldGroupConfigs
     *
     * @param array $fieldGroupConfigs
     * @return void
     */
    protected function deleteFieldGroups(array $fieldGroupConfigs): void {
        foreach ($fieldGroupConfigs as $fieldGroupConfig) {
            $name = $fieldGroupConfig['name'];
            $group = $this->getFieldGroupByName($name);
            if ($group) {
                Craft::$app->getFields()->deleteGroup($group);
            }
        }
    }

    /**
     * Create Fields based on $fieldConfigs
     *
     * @param array $fieldConfigs
     * @param string $groupName
     * @return void
     * @throws InvalidConfigException
     * @throws \Throwable
     */
    protected function createFields(array $fieldConfigs, string $groupName): void {
        $group = $this->getFieldGroupByName($groupName);
        if (!$group) {
            echo "FieldGroup {$groupName} doesn't exist" . PHP_EOL;
            throw new Exception('Fieldgroup not found: ' . $groupName);

            return;
        }
        $fieldsService = Craft::$app->getFields();
        foreach ($fieldConfigs as $fieldConfig) {
            $handle = $fieldConfig['handle'];
            if ($fieldsService->getFieldByHandle($handle)) {
                echo "Field {$handle} already exists" . PHP_EOL;
                throw new Exception('Field already exists: ' . $handle);
                continue;
            }
            $field = Craft::createObject(array_merge($fieldConfig, [
                'groupId' => $group->id,
            ]));

            if (!$fieldsService->saveField($field)) {
                throw new \Exception('Failed to save field: ' . $field->name);
            }
        }
    }

    /**
     * Delete Fields based on $fieldConfigs
     *
     * @param array $fieldConfigs
     * @return void
     * @throws \Throwable
     */
    protected function deleteFields(array $fieldConfigs): void {
        $fieldsService = Craft::$app->getFields();
        foreach ($fieldConfigs as $fieldConfig) {
            $handle = $fieldConfig['handle'];
            $field = $fieldsService->getFieldByHandle($handle);
            if ($field) {
                Craft::$app->getFields()->deleteField($field);
            }
        }
    }

    /**
     * Add the Fields to the $sectionHandle
     *
     * @param array $fieldConfigs
     * @param string $sectionHandle
     * @param string $entryTypeHandle
     * @return void
     * @throws \Throwable
     * @throws EntryTypeNotFoundException
     */
    protected function addFieldsToSection(array $fieldConfigs, string $sectionHandle, string $entryTypeHandle): void {
        $section = Craft::$app->sections->getSectionByHandle($sectionHandle);
        if (!$section) {
            echo "Section {$sectionHandle} doesn't exist" . PHP_EOL;
            return;
        }
        $entryType = $this->getEntryTypeByHandle($section, $entryTypeHandle);
        if (!$entryType) {
            echo "EntryType {$entryTypeHandle} doesn't exist" . PHP_EOL;
            return;
        }
        // Get all of our fields
        $elements = [];
        foreach ($fieldConfigs as $fieldConfig) {
            $handle = $fieldConfig['handle'];
            $field = Craft::$app->getFields()->getFieldByHandle($handle);
            if (!$field) {
                echo "Field {$handle} doesn't exist" . PHP_EOL;
                continue;
            }
            $elements[] = Craft::createObject([
                'class' => CustomField::class,
                'fieldUid' => $field->uid,
                'required' => false,
            ]);
        }
        // Just assign the fields to the first tab
        $layout = $entryType->getFieldLayout();
        $tabs = $layout->getTabs();
        $tabs[0]->setElements(array_merge($tabs[0]->getElements(), $elements));
        $layout->setTabs($tabs);
        $entryType->setFieldLayout($layout);
        Craft::$app->sections->saveEntryType($entryType);
    }

    /**
     * Remove the Fields from the Section
     * @param array $fieldConfigs
     * @param string $sectionHandle
     * @param string $entryTypeHandle
     * @return void
     */
    protected function removeFieldsFromSection(array $fieldConfigs, string $sectionHandle, string $entryTypeHandle): void {
        // Do nothing, these will be destroyed along with the Section
    }


    /**
     * Delete all entries from $sectionHandle
     *
     * @param string $sectionHandle
     * @return void
     * @throws \Throwable
     */
    protected function deleteEntryData(string $sectionHandle): void {
        // There are more efficient ways to do this, but whatever
        $section = Craft::$app->getSections()->getSectionByHandle($sectionHandle);
        if (!$section) {
            echo "Section {$sectionHandle} doesn't exist" . PHP_EOL;
            return;
        }
        $i = 0;
        foreach (Entry::find()->sectionId($section->id)->ids() as $entryId) {
            if (Craft::$app->elements->deleteElementById($entryId, null, null, true)) {
                echo "#{$i} - Deleted entry id {$entryId}" . PHP_EOL;
            } else {
                echo "#{$i} - Failed to delete entry id {$entryId}" . PHP_EOL;
            }
            $i++;
        }
    }

    /**
     * Get an EntryType by $entryTypeHandle
     *
     * @param Section $section
     * @param string $entryTypeHandle
     * @return EntryType|null
     */
    private function getEntryTypeByHandle(Section $section, string $entryTypeHandle): ?EntryType {
        $entryTypes = $section->getEntryTypes();
        foreach ($entryTypes as $entryType) {
            if ($entryType->handle === $entryTypeHandle) {
                return $entryType;
            }
        }
        return null;
    }

    /**
     * Get a field group by $name
     *
     * @param string $name
     * @return ?FieldGroup
     */
    private function getFieldGroupByName(string $name): ?FieldGroup {
        $groups = Craft::$app->getFields()->getAllGroups();
        foreach ($groups as $group) {
            if ($group->name === $name) {
                return $group;
            }
        }

        return null;
    }
}
