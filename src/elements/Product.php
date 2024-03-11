<?php

/**
 * Craft Shopify plugin for Craft CMS 4.x
 *
 * Bring Shopify products into Craft
 *
 * 
 * 
 */

namespace leogenot\craftshopify\elements;

use Craft;
use craft\base\Element;
use craft\elements\Entry;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\Queue;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use Exception;
use leogenot\craftshopify\CraftShopify;
use leogenot\craftshopify\elements\db\ProductQuery;
use leogenot\craftshopify\jobs\PushProductData;
use leogenot\craftshopify\records\ProductRecord;

/**

 *
 * @property int $shopifyId
 * @property string $jsonData
 * @property string $productType
 * @property string $bodyHtml
 * @property int $bodyHtmlMetafieldId
 */
class Product extends Element {

    /**
     * @var int
     */
    public int $shopifyId;

    /**
     * @var string
     */
    public string $jsonData;

    /**
     * @var string
     */
    public string $productType;

    /**
     * @var string
     */
    public string $bodyHtml;

    /**
     * @var null|int
     */
    public ?int $bodyHtmlMetafieldId = null;

    /**
     * @var bool
     */
    public $isWebhookUpdate = false;


    /**
     * @inheritdoc
     */
    public function getFieldLayout(): ?FieldLayout {
        return Craft::$app->fields->getLayoutByType(Product::class);
    }

    // Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string {
        return Craft::t('craft-shopify', 'Shopify Product');
    }

    /**
     * @inheritdoc
     */
    public static function hasContent(): bool {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function hasTitles(): bool {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function isLocalized(): bool {
        return true;
    }

    /**
     * @inheritdoc
     */
    protected static function defineDefaultTableAttributes(string $source): array {
        return [
            'title',
            'thumb',
            'productType',
            'shopifyId',
            'shopifyView',
            'shopifyEdit',
        ];
    }


    /**
     * @inheritdoc
     */
    protected static function defineTableAttributes(): array {
        return [
            // 'title' => Craft::t('app', 'Title'),
            // 'thumb' => ['label' => Craft::t('craft-shopify', 'Thumbnail')],
            // 'shopifyId' => ['label' => Craft::t('craft-shopify', 'Shopify ID')],
            // 'productType' => ['label' => Craft::t('craft-shopify', 'Product Type')],
            // 'shopifyView' => ['label' => Craft::t('craft-shopify', 'Shopify View URL')],
            // 'shopifyEdit' => ['label' => Craft::t('craft-shopify', 'Shopify Edit URL')],
            // 'dateUpdated' => ['label' => Craft::t('app', 'Date Updated')],
            // 'dateCreated' => ['label' => Craft::t('app', 'Date Created')],
            // 'datePublished' => ['label' => Craft::t('app', 'Date Published')],
            // 'publishedScope' => ['label' => Craft::t('craft-shopify', 'publishedScope')],
            // 'tags' => ['label' => Craft::t('craft-shopify', 'tags')],
            // 'status' => ['label' => Craft::t('craft-shopify', 'status')],
            // 'adminGraphQlApiId' => ['label' => Craft::t('craft-shopify', 'adminGraphQlApiId')],
            // 'variants' => ['label' => Craft::t('craft-shopify', 'variants')],
            // 'bodyHtml' => ['label' => Craft::t('craft-shopify', 'bodyHtml')],

            'title' => Craft::t('app', 'Title'),
            'thumb' => ['label' => Craft::t('craft-shopify', 'Thumbnail')],
            'shopifyId' => ['label' => Craft::t('craft-shopify', 'Shopify ID')],
            'productType' => ['label' => Craft::t('craft-shopify', 'Product Type')],
            'shopifyView' => ['label' => Craft::t('craft-shopify', 'Shopify View URL')],
            'shopifyEdit' => ['label' => Craft::t('craft-shopify', 'Shopify Edit URL')],
            'dateUpdated' => ['label' => Craft::t('app', 'Date Updated')],
            'dateCreated' => ['label' => Craft::t('app', 'Date Created')],
            'bodyHtml' => ['label' => Craft::t('craft-shopify', 'bodyHtml')],
        ];
    }

    /**
     * @return ProductQuery
     */
    public static function find(): ElementQueryInterface {
        return new ProductQuery(static::class);
    }

    /**
     * @inheritdoc
     */
    protected static function defineSources(string $context = null): array {
        $sources = [
            [
                'key' => '*',
                'label' => 'All Products',
                'criteria' => []
            ]
        ];

        $types = CraftShopify::$plugin->product->getAllProductTypes();

        foreach ($types as $type) {
            $sources[] = [
                'key' => $type,
                'label' => $type,
                'criteria' => [
                    'productType' => $type
                ]
            ];
        }

        return $sources;
    }

    /**
     * @inheritdoc
     */
    protected function tableAttributeHtml(string $attribute): string {
        switch ($attribute) {
            case 'shopifyEdit':
                $url = $this->getEditUrl();

                return Html::a('Edit on Shopify', $url, [
                    'rel' => 'noopener',
                    'target' => '_blank',
                ]);
            case 'shopifyView':
                $url = $this->getLiveUrl();

                return Html::a('View on Shopify', $url, [
                    'rel' => 'noopener',
                    'target' => '_blank',
                ]);
            case 'thumb':
                $data = Json::decode($this->jsonData);
                if (isset($data['image']['src'])) {
                    return Html::img($data['image']['src'], [
                        'width' => 50,
                    ]);
                }
                return '';
        }

        return parent::tableAttributeHtml($attribute);
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules(): array {
        $rules = parent::defineRules();
        $rules[] = [['shopifyId'], 'number', 'integerOnly' => true];
        $rules[] = [['jsonData', 'productType'], 'string'];

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getSupportedSites(): array {
        if ($this->siteId !== null) {
            return [$this->siteId];
        }

        return parent::getSupportedSites();
    }

    /**
     * @inheritdoc
     */
    public function getIsEditable(): bool {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getCpEditUrl(): ?string {
        return UrlHelper::url('craft-shopify/products/' . $this->id);
    }

    /**
     * @inheritdoc
     */
    public function getUriFormat(): string {
        return $this->getType()->uriFormat;
    }

    /**
     * Get the URL to edit the product on Shopify
     *
     * @return string
     */
    public function getEditUrl() {
        $settings = CraftShopify::$plugin->getSettings();
        $hostname = Craft::parseEnv($settings['hostname']);
        return "https://$hostname/admin/products/$this->shopifyId";
    }

    /**
     * Get the URL of the live product
     *
     * @return string
     */
    public function getLiveUrl() {
        $settings = CraftShopify::$plugin->getSettings();
        $hostname = Craft::parseEnv($settings['hostname']);
        return "https://$hostname/products/$this->slug";
    }

    // Indexes, etc.
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public function getEditorHtml(): string {
        $html = Craft::$app->getView()->renderTemplateMacro('_includes/forms', 'textField', [
            [
                'label' => Craft::t('app', 'Title'),
                'siteId' => $this->siteId,
                'id' => 'title',
                'name' => 'title',
                'value' => $this->title,
                'errors' => $this->getErrors('title'),
                'first' => true,
                'autofocus' => true,
                'required' => true
            ]
        ]);

        $html .= parent::getEditorHtml();

        return $html;
    }

    // Events
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public function beforeSave(bool $isNew): bool {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function afterSave(bool $isNew): void {
        if ($isNew) {
            $productRecord = new ProductRecord();
            $productRecord->id = $this->id;
            $productRecord->bodyHtmlMetafieldId = null;
        } else {
            $productRecord = ProductRecord::findOne($this->id);

            if (!$productRecord) {
                throw new Exception('Invalid Product ID: ' . $this->id);
            }
        }

        $productRecord->shopifyId = $this->shopifyId;
        $productRecord->jsonData = $this->jsonData;
        $productRecord->productType = $this->productType;
        $productRecord->bodyHtml = $this->bodyHtml;

        $productRecord->dateUpdated = $this->dateUpdated;
        $productRecord->dateCreated = $this->dateCreated;

        $productRecord->save(false);

        $this->id = $productRecord->id;

        // if (!$this->isWebhookUpdate) {
        //     Queue::push(new PushProductData([
        //         'productId' => $this->id,
        //     ]));
        // }

        parent::afterSave($isNew);
    }
    /**
     * @inheritdoc
     */
    public function beforeDelete(): bool {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function afterDelete(): void {
        parent::afterDelete();
    }

    /**
     * @inheritdoc
     */
    public function beforeMoveInStructure(int $structureId): bool {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function afterMoveInStructure(int $structureId): void {
        parent::afterMoveInStructure($structureId);
    }

    /**
     * Get the entry associated with this product
     *
     * @return Entry|null The associated entry, or null if not found
     */
    public function getEntry(): ?Entry {
        // Check if the product has been saved and has a shopifyId set
        if (!$this->id || !$this->shopifyId) {
            return null;
        }

        // Find the entry associated with this product by its shopifyId
        return Entry::find()
            ->section('products')
            ->shopifyId($this->shopifyId)
            ->one();
    }
}
