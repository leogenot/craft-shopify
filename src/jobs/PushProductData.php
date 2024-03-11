<?php

/**
 * craft-shopify module for Craft CMS 4.x
 *

 * 
 */


namespace leogenot\craftshopify\jobs;


use Craft;
use craft\queue\BaseJob;
use Exception;
use leogenot\craftshopify\CraftShopify;
use leogenot\craftshopify\elements\Product;
use craft\elements\Entry;


class PushProductData extends BaseJob {

    /**
     * @var array
     */
    public $productId = null;
    /**
     * @var Entry
     */
    public $entry = null;

    /**
     * @inheritdoc
     * @throws Exception
     */
    public function execute($queue): void {
        if ($this->productId) {

            $product = Entry::find()->section('products')->shopifyId([$this->productId])->one();
            if (!$product) {
                throw new Exception("Product {$this->productId} not found");
            }

            $this->setProgress($queue, 0, $product->title);
            CraftShopify::$plugin->product->pushDataToShopify($product);
        }
        $this->setProgress($queue, 0);
        //  else {
        //     throw new Exception('Product ID is required.' . $this->entry);
        // }
        // if ($this->entry) {
        //     $encodedEntry = json_encode($this->entry);
        //     // $this->setProgress($queue, 0, $decodedEntry->title);
        //     // CraftShopify::$plugin->product->pushDataToShopify($decodedEntry);
        //     $this->setProgress($queue, 0, $encodedEntry);
        //     CraftShopify::$plugin->product->pushDataToShopify($this->entry);
        // }
    }

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): string {
        return Craft::t('craft-shopify', "Sync Product ID {$this->productId} to Shopify");
    }
}
