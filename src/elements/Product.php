<?php

/**
 * craft-shopify module for Craft CMS 4.x
 *

 * 
 */

namespace leogenot\craftshopify\elements\db;

use craft\elements\db\ElementQuery;
use craft\helpers\Db;
use leogenot\craftshopify\elements\Product;


/**

 *
 * @method Product|null one($db = null)
 * @method Product[]|array|null all($db = null)
 */
class ProductQuery extends ElementQuery {
    /**
     * @var int ID of product within Shopify
     */
    public $shopifyId;

    /**
     * @var string Product Type
     */
    public $productType;

    /**
     * @param mixed $value
     * @return $this
     */
    public function shopifyId($value): ProductQuery {
        $this->shopifyId = $value;
        return $this;
    }

    public function productType($value): ProductQuery {
        $this->productType = $value;
        return $this;
    }

    /**
     * @inheritdoc
     */
    protected function beforePrepare(): bool {
        // join in the products table
        $this->joinElementTable('shopifyproducts');

        // select the price column
        $this->query->select([
            'shopifyproducts.shopifyId',
            'shopifyproducts.jsonData',
            'shopifyproducts.productType',
            'shopifyproducts.bodyHtml',
            'shopifyproducts.vendor',
            'shopifyproducts.bodyHtmlMetafieldId'
        ]);

        if ($this->shopifyId) {
            $this->subQuery->andWhere(Db::parseParam('shopifyproducts.shopifyId', $this->shopifyId));
        }

        if ($this->productType) {
            $this->subQuery->andWhere(Db::parseParam('shopifyproducts.productType', $this->productType));
        }

        return parent::beforePrepare();
    }
}
