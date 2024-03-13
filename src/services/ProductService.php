<?php

/**
 * Service responsible for handling Shopify products in Craft CMS.
 */

namespace leogenot\craftshopify\services;

use DateTime;

use benf\neo\elements\Block;
use Craft;
use craft\base\Component;
use craft\base\ElementInterface;
use craft\elements\Entry;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\helpers\ProjectConfig;
use craft\helpers\Queue;
use craft\helpers\StringHelper;
use craft\models\FieldLayout;
use craft\web\View;
use leogenot\craftshopify\CraftShopify;
use leogenot\craftshopify\elements\Product;
use leogenot\craftshopify\jobs\PushProductData;
use leogenot\craftshopify\records\ProductRecord;
use leogenot\craftshopify\services\ShopifyService;
use PHPShopify\Exception\ApiException;
use PHPShopify\Exception\CurlException;
use Throwable;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use yii\base\ErrorException;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\web\ServerErrorHttpException;

class ProductService extends Component {
    const CONFIG_PRODUCT_FIELDLAYOUT_KEY = 'craftshopify.sectionOptions';
    const METAFIELD_NAMESPACE = 'cms';

    /**
     * Get the model to put Shopify data into
     *
     * @param int $shopifyId Shopify product ID
     * @return Product Product model instance
     */
    public function getProductModel($shopifyId): Product {
        // Check if product with given Shopify ID already exists
        if (!$product = Product::find()->shopifyId($shopifyId)->one()) {
            // Create a new product model if not found
            $product = new Product();
            $product->shopifyId = (int)$shopifyId;

            return $product;
        }

        return $product;
    }

    /**
     * Populate the Craft element with Shopify response data
     *
     * @param Product $product Product model instance
     * @param array $shopifyProduct Shopify product data
     */
    public function populateProductModel(Product $product, $shopifyProduct) {
        // Encode Shopify product data as JSON and assign to product model attributes
        $product->jsonData = Json::encode($shopifyProduct);
        $product->title = $shopifyProduct['title'];
        $product->slug = $shopifyProduct['handle'];
        $product->dateCreated = new DateTime($shopifyProduct['created_at']);
        $product->dateUpdated = new DateTime($shopifyProduct['updated_at']);
        $product->productType = $shopifyProduct['product_type'];
        $product->bodyHtml = $shopifyProduct['body_html'] ?? '';
        $product->vendor = $shopifyProduct['vendor'] ?? '';
    }
    /**
     * Populate the Craft entry with Shopify response data
     *
     * @param Entry $entry Craft entry instance
     * @param array $shopifyProduct Shopify product data
     */
    public function populateEntryModel(Entry $entry, $shopifyProduct) {
        // Populate entry fields with Shopify data
        $entry->title = $shopifyProduct['title'];
        $entry->slug = $shopifyProduct['handle'];
        $entry->setFieldValue('shopifyId', $shopifyProduct['id']);
        $entry->setFieldValue('productDescription', $shopifyProduct['body_html']);
        $entry->setFieldValue('productJsonData',  json_encode($shopifyProduct));
        $entry->setFieldValue('productVendor', $shopifyProduct['vendor']);
    }

    /**
     * Update entry data based on Shopify response
     *
     * @param array|null $shopifyData Shopify product data
     * @return Entry|null Updated entry model instance or null if no data provided
     */
    public function updateEntry(array $shopifyData = null) {
        // Return null if no Shopify data provided
        if (!$shopifyData) {
            return null;
        }

        // Get Shopify ID from data
        $shopifyId = $shopifyData['id'];

        // Get or create entry
        $entry = Entry::find()->section('products')->shopifyId([$shopifyId])->one();
        if (!$entry) {
            // Create a new entry if not found
            $entry = new Entry();
            $entry->sectionId = Craft::$app->getSections()->getSectionByHandle('products')->id;
            // Set the entry title to the Shopify product ID when creating a new entry
            $entry->title = $shopifyId;
        }

        // Populate entry with Shopify data
        $this->populateEntryModel($entry, $shopifyData);

        // Save entry
        if (!Craft::$app->getElements()->saveElement($entry)) {
            // Handle saving errors if needed
            return null;
        }

        return $entry;
    }


    /**
     * Get the Craft element for a Shopify ID
     *
     * @param int $productId Craft product ID
     * @return Product|null Craft product model instance or null if not found
     */
    public function getProductById(int $productId) {
        return Product::find()->id($productId)->one();
    }

    /**
     * Retrieve all product types from database
     *
     * @return array List of product types
     */
    public function getAllProductTypes() {
        // Retrieve distinct product types from ProductRecord
        $types = ProductRecord::find()
            ->select(['productType'])
            ->distinct()
            ->column();

        // Remove empty values
        ArrayHelper::removeValue($types, '');

        return $types;
    }

    /**
     * Update all products related to an entry
     *
     * @param Entry $entry Entry element
     * @throws Exception
     */
    public function updateRelatedProducts(Entry $entry) {
        // Push job to update related products
        Craft::$app->getQueue()->push(new PushProductData([
            'productId' => $entry->shopifyId,
        ]));
        CraftShopify::$plugin->product->pushDataToShopify($entry);
    }

    /**
     * Update Data in Shopify
     *
     * @param Product $product Product model instance
     * @throws ApiException
     * @throws CurlException
     * @throws Exception
     */
    public function pushDataToShopify(Entry $entry) {
        // Get Shopify client
        $client = CraftShopify::$plugin->shopify->getClient();

        $data = [
            'id' => $entry->shopifyId, // Shopify ID of the product
            'title' => $entry->title, // Map Craft CMS title to Shopify title
            'handle' => $entry->slug, // Map Craft CMS title to Shopify title
            'body_html' => $entry->productDescription, // Map Craft CMS title to Shopify title
            'vendor' => $entry->productVendor, // Map Craft CMS title to Shopify title
            // Map other Craft CMS fields to corresponding Shopify fields as needed
        ];

        // Update the product on Shopify
        try {
            CraftShopify::$plugin->shopify->pushProduct($data);
        } catch (ApiException $e) {
            Craft::error('Failed to update product on Shopify: ' . $e->getMessage(), __METHOD__);
            throw $e; // Rethrow the exception to handle it at a higher level if needed
        }
    }


    /**
     * Update product data based on Shopify response
     *
     * @param array|null $shopifyData Shopify product data
     * @return Product|null Updated product model instance or null if no data provided
     */
    public function updateProduct(array $shopifyData = null) {
        // Return null if no Shopify data provided
        if (!$shopifyData) {
            return;
        }

        // Get Shopify ID from data
        $shopifyId = $shopifyData['id'];

        // Get or create product model
        $product = $this->getProductModel($shopifyId);
        // Populate product model with Shopify data
        $this->populateProductModel($product, $shopifyData);

        return $product;
    }

    /**
     * Delete a product by Shopify ID
     *
     * @param int $shopifyId Shopify product ID
     * @return bool True if product deleted successfully, false otherwise
     * @throws Throwable
     */
    public function deleteByShopifyId($shopifyId): bool {
        // Find product by Shopify ID
        $product = Product::find()->shopifyId($shopifyId)->one();
        // If product not found, log and return false
        if (!$product) {
            Craft::info("Shopify ID $shopifyId not found");
            return false;
        }

        // Delete product from Craft CMS
        if (!Craft::$app->getElements()->deleteElement($product, true)) {
            $errors = Json::encode($product->getErrors());
            Craft::error("Failed to delete product $product->id: $errors");
            return false;
        }

        // Log successful deletion
        Craft::info("Shopify ID: $shopifyId / Craft ID: $product->id successfully deleted.");
        return true;
    }
}
