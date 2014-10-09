<?php
class Elgentos_GoogleTagManager_Helper_Data extends Mage_Core_Helper_Abstract
{
    const XML_PATH_ACTIVE = 'googletagmanager/general/active';
    const XML_PATH_CONTAINER = 'googletagmanager/general/containerid';

    const XML_PATH_DATALAYER_TRANSACTIONS  = 'google/googletagmanager/datalayertransactions';
    const XML_PATH_DATALAYER_TRANSACTIONTYPE = 'google/googletagmanager/datalayertransactiontype';
    const XML_PATH_DATALAYER_TRANSACTIONAFFILIATION = 'google/googletagmanager/datalayertransactionaffiliation';

    const XML_PATH_DATALAYER_VISITORS = 'google/googletagmanager/datalayervisitors';

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
     * Add transaction data to the data layer?
     *
     * @return bool
     */
    public function isDataLayerTransactionsEnabled()
    {
        return Mage::getStoreConfig(self::XML_PATH_DATALAYER_TRANSACTIONS);
    }

    /**
     * Get the transaction type.
     *
     * @return string
     */
    public function getTransactionType() {
        if (!Mage::getStoreConfig(self::XML_PATH_DATALAYER_TRANSACTIONTYPE)) return '';
        return Mage::getStoreConfig(self::XML_PATH_DATALAYER_TRANSACTIONTYPE);
    }

    /**
     * Get the transaction affiliation.
     *
     * @return string
     */
    public function getTransactionAffiliation() {
        if (!Mage::getStoreConfig(self::XML_PATH_DATALAYER_TRANSACTIONAFFILIATION)) return '';
        return Mage::getStoreConfig(self::XML_PATH_DATALAYER_TRANSACTIONAFFILIATION);
    }

    /**
     * Add visitor data to the data layer?
     *
     * @return bool
     */
    public function isDataLayerVisitorsEnabled()
    {
        return Mage::getStoreConfig(self::XML_PATH_DATALAYER_VISITORS);
    }
}