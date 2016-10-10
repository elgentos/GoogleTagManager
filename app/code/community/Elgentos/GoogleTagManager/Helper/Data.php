<?php
class Elgentos_GoogleTagManager_Helper_Data extends Mage_Core_Helper_Abstract
{
    const XML_PATH_ACTIVE = 'googletagmanager/general/active';
    const XML_PATH_CONTAINER = 'googletagmanager/general/containerid';

    const XML_PATH_PRODUCT_IMPRESSIONS = 'googletagmanager/events/measure_product_impressions';
    const XML_PATH_PRODUCT_DETAIL_IMPRESSIONS = 'googletagmanager/events/measure_product_detail_impressions';
    const XML_PATH_PRODUCT_CLICKS = 'googletagmanager/events/measure_product_clicks';
    const XML_PATH_CART_MODIFICATION_TRACKING = 'googletagmanager/events/measure_add_removal_from_cart';
    const XML_PATH_PURCHASE_TRACKING = 'googletagmanager/events/measure_purchases';

    const PRODUCT_IMPRESSIONS_REGISTRY_KEY = 'googletagmanager_product_impressions_registry_key';

    const CATEGORY_SEPARATOR = '|';

    /**
     * Determine if GTM is ready to use.
     *
     * @return bool
     */
    public function isGoogleTagManagerAvailable()
    {
        return Mage::getStoreConfig(self::XML_PATH_CONTAINER) && Mage::getStoreConfigFlag(self::XML_PATH_ACTIVE);
    }

    /**
     * Get the GTM container ID.
     *
     * @return string
     */
    public function getContainerId() {
        return Mage::getStoreConfig(self::XML_PATH_CONTAINER);
    }

    /**
     * Measure product impressions?
     *
     * @return bool
     */
    public function isProductImpressionsMeasuringEnabled()
    {
        return Mage::getStoreConfig(self::XML_PATH_PRODUCT_IMPRESSIONS);
    }

    /**
     * Measure product detail impressions?
     *
     * @return bool
     */
    public function isProductDetailImpressionsMeasuringEnabled()
    {
        return Mage::getStoreConfig(self::XML_PATH_PRODUCT_DETAIL_IMPRESSIONS);
    }
    /**
     * Measure product clicks?
     *
     * @return bool
     */
    public function isProductClicksMeasuringEnabled()
    {
        return Mage::getStoreConfig(self::XML_PATH_PRODUCT_CLICKS);
    }

    /**
     * Measure cart modifications?
     *
     * @return bool
     */
    public function isCartModificationTrackingEnabled()
    {
        return Mage::getStoreConfig(self::XML_PATH_CART_MODIFICATION_TRACKING);
    }

    /**
     * Measure purchases?
     *
     * @return bool
     */
    public function isPurchaseTrackingEnabled()
    {
        return Mage::getStoreConfig(self::XML_PATH_PURCHASE_TRACKING);
    }

    /**
     * Fetch product impressions list for impressions data layer
     *
     * @return array
     */
    public function getProductImpressionsList()
    {
        if (! $this->isProductImpressionsMeasuringEnabled())
            return;

        $stash = Mage::registry(self::PRODUCT_IMPRESSIONS_REGISTRY_KEY);

        if (!$stash) {
            $stash = [];
        }

        return $stash;
    }

    /**
     * Add products to product impressions list for impressions data layer
     *
     * @return array
     */
    public function addProductImpressionsToList($list, $productsCollection)
    {
        if (! $this->isProductImpressionsMeasuringEnabled())
            return;

        if (! $productsCollection->getSize())
            return;

        $stash = $this->getProductImpressionsList();

        if(!empty($stash)) {
            Mage::unregister(self::PRODUCT_IMPRESSIONS_REGISTRY_KEY);
        }

        $products = array();
        $position = 0;
        foreach($productsCollection as $product) {
            $products[] = $this->getProductData($product, ['list' => $list, 'position' => $position]);
            $position++;
        }

        $stash = array_merge($stash, $products);

        Mage::register(self::PRODUCT_IMPRESSIONS_REGISTRY_KEY, $stash);
    }

    /**
     * Get product data in UA format for data layer
     *
     * @return array
     */
    public function getProductData($product, $extraAttributes = array())
    {
        $data = [
            'name' => $product->getName(),
            'id' => $product->getSku(),
            'price' => $product->getFinalPrice(),
            'brand' => $product->getAttributeText('manufacturer'),
            'category' => $this->_getProductCategoryNames($product),
            //'variant' => null,
        ];

        if(!empty($extraAttributes)) {
            $data = array_merge($data, $extraAttributes);
        }

        return $data;
    }

    /**
     * Get category names separated from product
     *
     * @return string
     */
    private function _getProductCategoryNames($product)
    {
        $categoryCollection = Mage::getModel('catalog/category')->getCollection()
            ->addFieldToFilter('entity_id', array('in' => $product->getCategoryIds()))
            ->addFieldToFilter('level', array('gt' => 1))
            ->addAttributeToSelect('name');

        $categoryNames = array();
        foreach ($categoryCollection as $category) {
            $categoryNames[] = $category->getName();
        }

        return implode(self::CATEGORY_SEPARATOR, $categoryNames);
    }
}