<?php

/**
 * Class Elgentos_GoogleTagManager_Block_Gtm
 *
 * Adds data layer objects to the DOM for Enhanced Ecommerce (UA)
 *
 * General documentation here: https://developers.google.com/tag-manager/enhanced-ecommerce
 * Dev docs here: https://developers.google.com/analytics/devguides/collection/analyticsjs/enhanced-ecommerce#ecommerce-data
 *
 */

class Elgentos_GoogleTagManager_Block_Gtm extends Mage_Core_Block_Template
{
    /** @var Elgentos_GoogleTagManager_Helper_Data */
    private $_helper = null;
    private $_dataLayer = array();
    private $_currencyCode = 'EUR';

    public function _construct()
    {
        $this->_helper = Mage::helper('googletagmanager');
        $this->_currencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();
    }

    /**
     * Generate JavaScript for the container snippet.
     *
     * @return string
     */
    protected function _getContainerSnippet()
    {
        // Get the container ID.
        $containerId = $this->_helper->getContainerId();

        // Render the container snippet JavaScript.
        return "<noscript><iframe src=\"//www.googletagmanager.com/ns.html?id=" . $containerId . "\"
    height=\"0\" width=\"0\" style=\"display:none;visibility:hidden\"></iframe></noscript>
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    '//www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','" . $containerId . "');</script>\n";
    }

    /**
     * Generate JavaScript for the data layer.
     *
     * @return string|null
     */

    protected function _getDataLayer()
    {
        if (
            Mage::registry('product')
            && $this->getRequest()->getControllerName() == 'product'
            && $this->_helper->isProductDetailImpressionsMeasuringEnabled()
        ) {
            $this->_getProductDataLayer();
        } elseif (
            $this->getRequest()->getControllerName() == 'onepage'
            && $this->getRequest()->getActionName() == 'success'
            && $this->_helper->isPurchaseTrackingEnabled()
        ) {
            $this->_getSuccessDataLayer();
        }

        /* Fetch product impression layers from events */
        if($this->_helper->isProductImpressionsMeasuringEnabled()) {
            $this->_getProductImpressionsDataLayer();
        }

        if (!empty($this->_dataLayer)) {
            $script = "<script>\n";
            $script .= 'document.addEventListener("DOMContentLoaded", function(event) {' . "\n";
            $script .= "if (dataLayer) {\n";
            foreach ($this->_dataLayer as $type => $layer) {
                $dataJsonPretty = json_encode($layer, (Mage::getIsDeveloperMode() ? JSON_PRETTY_PRINT : null));
                $script .= "dataLayer.push(" . $dataJsonPretty . "); // " . $type . "\n";
                if(Mage::getIsDeveloperMode()) {
                    //$script .= "console.log('Pushing data for " . $type ."'); console.log(JSON.stringify(" . $dataJsonPretty . ", true, 2));\n";
                }
            }
            $script .= "}";
            if(Mage::getIsDeveloperMode()) {
                 $script .= " else { console.log('No dataLayer found.') }";
            }
            $script .= "\n" . '});' . "\n";
            $script .= "</script>\n";
        }

        // Generate the data layer JavaScript output.
        if (!empty($script)) {
            return $script;
        } else {
            return null;
        }
    }

    protected function _getProductDataLayer()
    {
        $product = Mage::registry('current_product');

        $layer = [
            'ecommerce' => [
                'detail' => [
                    'actionField' => ['list' => 'Product Page'],
                    'products' => [
                        $this->_helper->getProductData($product)
                    ]
                ]
            ]
        ];

        $this->_addToDataLayer('product_data_layer', $layer);
    }

    protected function _getProductImpressionsDataLayer()
    {
        $productLayerData = $this->_helper->getProductImpressionsList();

        if(!empty($productLayerData)) {
            $layer = [
                'ecommerce' => [
                    'currencyCode' => $this->_currencyCode,
                    'impressions' => $productLayerData
                ]
            ];

            $this->_addToDataLayer('product_impression_data_layer', $layer);
        }
    }

    protected function _getSuccessDataLayer()
    {
        $orderId = Mage::getSingleton('checkout/session')->getLastOrderId();

        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order')->load($orderId);

        $orderItems = array();
        /** @var Mage_Sales_Model_Order_item $orderItem */
        foreach ($order->getAllVisibleItems() as $orderItem) {
            $orderItems[] = $this->_helper->getProductData($orderItem->getProduct(),
                [
                    'price' => $orderItem->getBasePrice(),
                    'quantity' => $orderItem->getQtyOrdered()
                ]
            );
        }

        if (!empty($orderItems)) {
            $layer = [
                'event' => 'purchase',
                'ecommerce' => [
                    'purchase' => [
                        'actionField' => [
                            'id' => $order->getIncrementId(),
                            'affiliation' => Mage::app()->getStore()->getCode(),
                            'revenue' => $order->getBaseGrandTotal(),
                            'tax' => $order->getBaseTaxAmount(),
                            'shipping' => $order->getBaseShippingAmount(),
                            'coupon' => ($order->getCouponCode() ? $order->getCouponCode() : '')
                        ],
                        'products' => $orderItems
                    ]
                ]
            ];

            $this->_addToDataLayer('success_data_layer', $layer);
        }
    }

    protected function _getTotalCartValue()
    {
        $total = Mage::helper('checkout/cart')->getQuote()->getGrandTotal();
        return $total;
    }

    /**
     * Render Google Tag Manager code
     *
     * @return string
     */
    protected function _toHtml()
    {
        if (!$this->_helper->isGoogleTagManagerAvailable()) return '';
        return parent::_toHtml();
    }

    /**
     * Add an array to the dataLayer
     *
     * @return null
     */
    protected function _addToDataLayer($type, $layer)
    {
        $dataLayer = $this->_dataLayer;

        Mage::dispatchEvent('elgentos_googletagmanager_add_layer_to_datalayer',
            [
                'type' => $type,
                'layer' => $layer
            ]
        );

        $dataLayer[$type] = $layer;
        $this->_dataLayer = $dataLayer;
    }
}
