<?php

/**
 * craft-shopify module for Craft CMS 4.x
 *
 */

namespace leogenot\craftshopify\services;

use Craft;
use craft\base\Component;
use craft\helpers\ArrayHelper;
use leogenot\craftshopify\CraftShopify;
use PHPShopify\ShopifySDK;
use Exception;

/**
 * Service responsible for interfacing with Shopify API.
 * Handles authentication and API requests.
 *
 */
class ShopifyService extends Component {
    protected $client = null;

    /**
     * Get the Shopify client instance.
     *
     * @return ShopifySDK
     * @throws \PHPShopify\Exception\ApiException
     * @throws \PHPShopify\Exception\CurlException
     */
    public function getClient() {
        if (!$this->client) {
            $settings = CraftShopify::$plugin->getSettings();

            // Initialize Shopify SDK client with provided settings
            $this->client = ShopifySDK::config([
                'ShopUrl' => Craft::parseEnv($settings['hostname']),
                'ApiKey' => Craft::parseEnv($settings['apiKey']),
                'Password' => Craft::parseEnv($settings['apiPassword'])
            ]);
        }

        return $this->client;
    }

    /**
     * Retrieves all products from the Shopify store.
     *
     * @param array $params Additional parameters for filtering products.
     * @return array Fetched products.
     * @throws \PHPShopify\Exception\ApiException
     * @throws \PHPShopify\Exception\CurlException
     */
    public function getAllProducts(array $params = []): array {
        $shopify = $this->getClient();
        $resource = $shopify->Product;

        // Prepare pagination parameters
        $nextPageParams = ArrayHelper::merge([
            'limit' => 100
        ], $params);

        // If -1 was passed in for the limit, paginate to get all products
        if ($nextPageParams['limit'] === -1) {
            $products = [];
            $nextPageParams['limit'] = 100;

            do {
                $response = $resource->get($nextPageParams);
                $nextPageParams = $resource->getNextPageParams();
                $products = ArrayHelper::merge($products, $response);
            } while (count($nextPageParams) > 0);

            return $products;
        } else {
            // Retrieve products without pagination
            return $resource->get($nextPageParams);
        }
    }

    /**
     * Retrieves product information from Shopify by ID.
     *
     * @param string|int $id Product ID.
     * @return array|null Product information if found, null otherwise.
     * @throws \PHPShopify\Exception\ApiException
     * @throws \PHPShopify\Exception\CurlException
     */
    public function getProductById($id): ?array {
        // Return null if no ID provided
        if (!$id) {
            return null;
        }

        // Retrieve product information from Shopify by ID
        return $this->getClient()->Product($id)->get();
    }

    /**
     * Pushes a product to Shopify.
     *
     * @param array $productData The data of the product to be pushed to Shopify.
     * @return array The response from Shopify after pushing the product.
     * @throws \PHPShopify\Exception\ApiException
     * @throws \PHPShopify\Exception\CurlException
     */
    public function pushProduct(array $productData): array {
        // Get the Shopify client
        $shopify = $this->getClient();

        // Create or update the product on Shopify
        if (isset($productData['id'])) {
            // Update existing product
            $productId = $productData['id'];
            // unset($productData['id']); // Remove the Shopify ID from the data
            return $shopify->Product($productId)->put($productData);
        } else {
            // Create new product
            return $shopify->Product->post($productData);
        }
    }
}
