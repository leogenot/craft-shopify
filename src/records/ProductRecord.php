<?php

/**
 * craft-shopify module for Craft CMS 4.x
 *

 * 
 */


namespace leogenot\craftshopify\records;


use craft\db\ActiveQuery;
use craft\db\ActiveRecord;
use craft\records\Element;

/**

 *
 * @property int $shopifyId
 * @property string $jsonData
 * @property string $productType
 * @property int $id
 * @property string $bodyHtml
 * @property int $bodyHtmlMetafieldId
 */
class ProductRecord extends ActiveRecord {
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return '{{%shopifyproducts}}';
    }

    public function getElement(): ActiveQuery {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }
}
