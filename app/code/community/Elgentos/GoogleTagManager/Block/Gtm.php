<?php
class Elgentos_GoogleTagManager_Block_Gtm extends Mage_Core_Block_Template
{
    /**
     * Generate JavaScript for the container snippet.
     *
     * @return string
     */
    protected function _getContainerSnippet()
    {
        // Get the container ID.
        $containerId = Mage::helper('googletagmanager')->getContainerId();

        // Render the container snippet JavaScript.
        return "<noscript><iframe src=\"//www.googletagmanager.com/ns.html?id=".$containerId."\"
    height=\"0\" width=\"0\" style=\"display:none;visibility:hidden\"></iframe></noscript>
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    '//www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','".$containerId."');</script>\n";
    }
    
    /**
     * Generate JavaScript for the data layer.
     *
     * @return string|null
     */
     
    protected function _getDataLayer()
    {
        $dataPush = array();

        if (Mage::registry('product') && $this->getRequest()->getControllerName() == 'product') {
            $dataPush = $this->_getProductDataLayer();
        } elseif ($this->getRequest()->getControllerName() == 'category') {
            $dataPush = $this->_getCategoryDataLayer();
        } elseif ($this->getRequest()->getControllerName() == 'cart' || in_array("onestepcheckout_index_index", $this->getLayout()->getUpdate()->getHandles())) {
            $dataPush = $this->_getCartDataLayer();
        } elseif ($this->getRequest()->getControllerName() == 'onepage') {
            $dataPush = $this->_getCartDataLayer();
        } elseif (Mage::getSingleton('cms/page')->getIdentifier() == 'home'){
            $dataPush = $this->_getHomeDataLayer();
        } else {
            $dataPush = $this->_getDefaultDataLayer();
        }
        
        if (!empty($dataPush)) {
            $script = "<script>
            var google_tag_params = {
            ".$dataPush."
            };
            dataLayer = [{
            \"google_tag_params\": window.google_tag_params
            }];</script>\n";
        }

        // Generate the data layer JavaScript.
        if (!empty($script)) {
          return $script;
        } else {
          return null;
        }
    }

    
    protected function _getProductDataLayer()
    {
        $product = Mage::registry('current_product');

        $dataPush = "ecomm_prodid: '" . $product->getSku() . "', \n";
        $dataPush .= "ecomm_pagetype: 'product', \n";
        $dataPush .= "ecomm_pname: '". $product->getName()."', \n";
        $dataPush .=  "ecomm_pcat: [".$this->_getProductCategoryNames($product)."], \n";
        $dataPush .= "ecomm_totalvalue: '" . substr($product->getPrice(), 0, -2) ."'";
        
        return $dataPush;
    }

    protected function _getCartDataLayer()
    {
        $session = Mage::getSingleton('checkout/session');
        if (count($session->getQuote()->getAllItems()) > 0) {
            $productIds = null;
            $itemcount = count($session->getQuote()->getAllItems());
            $i = 0;
            foreach($session->getQuote()->getAllItems() as $item) {
                $product = Mage::getModel('catalog/product')->getCollection()
                            ->addFieldToFilter('sku',$item->getSku())
                            ->addAttributeToSelect('sku')
                            ->getFirstItem();                
                $productIds .= "'".str_replace("'","", $product->getSku())."'";
                if ($i === $itemcount - 1) continue;
                    $i++;
                    $productIds .= ",";
            }
        
            $dataPush =  "ecomm_prodid: [".$productIds."], \n";
            $dataPush .=  "ecomm_quantity: '".$itemcount."', \n";
            $dataPush .= "ecomm_pagetype: 'cart', \n";
            $dataPush .= "ecomm_totalvalue: '".$this->_getTotalCartValue()."'";
        } else {
            $dataPush = "ecomm_prodid: '', \n";
            $dataPush .= "ecomm_pagetype: 'cart', \n";
            $dataPush .= "ecomm_totalvalue: ''";  
        }
        
        return $dataPush;
        
    }       
    
    protected function _getHomeDataLayer()
    {
        $dataPush = "ecomm_prodid: '', \n";
        $dataPush .= "ecomm_pagetype: 'home', \n";
        $dataPush .= "ecomm_totalvalue: ''";
        return $dataPush;
    }
    
    protected function _getDefaultDataLayer()
    {
        $dataPush = "ecomm_prodid: '', \n";
        $dataPush .= "ecomm_pagetype: 'siteview', \n";
        $dataPush .= "ecomm_totalvalue: ''";  
        return $dataPush;
    }

    protected function _getCategoryDataLayer()
    {
        $dataPush = "ecomm_prodid: '', \n";
        $dataPush .= "ecomm_pagetype: 'category', \n";
        $dataPush .= "ecomm_totalvalue: ''";
        return $dataPush;
    }    
    
    protected function _getProductCategoryNames($product)
    {
        $categoryCollection = $product->getCategoryIds();
        $categories = null;
        $length = count($categoryCollection);
        $i = 0;
        foreach ($categoryCollection as $category) {
            $category = $category = Mage::getModel('catalog/category')->getCollection()
                ->addFieldToFilter('entity_id',$category)
                ->addAttributeToSelect('name')
                ->getFirstItem();
            $categories .= "'".str_replace("'","", $category->getName())."'";
            if ($i === $length - 1) continue;
            $i++;
            $categories .= ",";
        }
        
        return $categories;
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
        if (!Mage::helper('googletagmanager')->isGoogleTagManagerAvailable()) return '';
        return parent::_toHtml();
    }
}
