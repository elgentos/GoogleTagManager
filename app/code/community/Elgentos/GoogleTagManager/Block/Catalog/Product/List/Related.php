<?php
/**
 * Created by PhpStorm.
 * User: peterjaap
 * Date: 22/09/16
 * Time: 15:35
 */ 
class Elgentos_GoogleTagManager_Block_Catalog_Product_List_Related extends Mage_Catalog_Block_Product_List_Related {

    public function getItems()
    {
        if (! Mage::registry('elgentos_googletagmanager_product_impressions_dispatched')) {
            Mage::dispatchEvent('elgentos_googletagmanager_product_impressions',
                [
                    'list' => 'Related Products',
                    'products' => $this->_itemCollection
                ]
            );
        }

        Mage::register('elgentos_googletagmanager_product_impressions', true);

        return $this->_itemCollection;
    }

}