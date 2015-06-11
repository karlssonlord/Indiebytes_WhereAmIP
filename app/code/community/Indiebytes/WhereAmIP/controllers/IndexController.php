<?php

/**
 * Class Indiebytes_WhereAmIP_IndexController
 */
class Indiebytes_WhereAmIP_IndexController extends Mage_Core_Controller_Front_Action
{
    /**
     * Default index action
     * This will show the country selector and also handle the selection
     * of country (POST).
     *
     * @throws Mage_Core_Exception
     */
    public function indexAction()
    {
        /**
         * Fetch the country code
         */
        if ($this->getRequest()->getPost('country')) {
            $countryCode = $this->getRequest()->getPost('country');
        } elseif (Mage::getSingleton('core/session')->getCountryCode()) {
            $countryCode = Mage::getSingleton('core/session')->getCountryCode();
        } else {
            $countryCode = Mage::getStoreConfig('general/country/default');
        }

        /**
         * Make sure the country code is upper case
         */
        $countryCode = strtoupper($countryCode);

        /**
         * Fetch all active countries
         */
        $countries = Mage::helper('whereamip')->getActiveCountries();

        /**
         * Make sure the request is set in the array
         */
        if (array_key_exists($countryCode, $countries)) {
            $storeCode = $countries[$countryCode]['code'];
            Mage::getSingleton('core/session')->setCountryCode($countryCode);
            Mage::getSingleton('core/session')->setStoreCode($storeCode);
            setcookie("store", $storeCode, time() + 60 * 60 * 24 * 30, "/");

            /**
             * Load the store
             */
            $store = Mage::app()->getStore($storeCode);

            /**
             * Generate redirect route
             * If no ref variable is given, store default page will be used
             */
            if ($this->getRequest()->getPost('ref')) {
                if ($store) {
                    $url = $store->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);
                } else {
                    $url = $this->getRequest()->getPost('ref');
                }
            } else {
                $url = $store->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);
            }

            /**
             * Redirect PHP way
             */
            header("Location: " . $url);
            exit;
        } else {
            Mage::getSingleton('core/session')->setError(
                sprintf($this->__('Country not found: %s.'), $countryCode)
            );
        }

        $this->loadLayout();
        $this->getLayout()->getBlock('changeCountry');
        $this->renderLayout();
    }

    /**
     * Generate pixel that probably will be loaded on all domains
     */
    public function pixelAction()
    {
        $countryCode = $this->getRequest()->getParam('code');
        $countryCode = strtoupper($countryCode);
        $countries = Mage::helper('whereamip')->getActiveCountries();

        if (array_key_exists($countryCode, $countries)) {
            $storeCode = $countries[$countryCode]['code'];
            Mage::getSingleton('core/session')->setCountryCode($countryCode);
            Mage::getSingleton('core/session')->setStoreCode($storeCode);
            setcookie("wstore", $storeCode, time() + 60 * 60 * 24 * 30, "/");
        }

        $this->getResponse()->setHeader('Content-type', 'image/png', true);
        $this->getResponse()->setBody(
            base64_decode(
                'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABAQMAAAAl21bKAAAAA1BMVEUAAACnej3aAAAAAXRSTlMAQObYZgAAAApJREFUCNdjYAAAAAIAAeIhvDMAAAAASUVORK5CYII='
            )
        );
    }
}
