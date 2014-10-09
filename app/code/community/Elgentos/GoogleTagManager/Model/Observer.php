<?php
class Elgentos_GoogleTagManager_Model_Observer
{
    /**
     * Add order data to GTM block (for subsequent rendering in the data layer).
     *
     * @param Varien_Event_Observer $observer
     */
    public function setGoogleTagManagerTransactionData(Varien_Event_Observer $observer)
    {
        $orderIds = $observer->getEvent()->getOrderIds();
        if (empty($orderIds) || !is_array($orderIds)) return;
        $block = Mage::app()->getFrontController()->getAction()->getLayout()->getBlock('google_tag_manager');
        if ($block) $block->setOrderIds($orderIds);
    }
}
