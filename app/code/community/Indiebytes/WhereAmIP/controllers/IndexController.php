<?php
class Indiebytes_WhereAmIP_IndexController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        if ($this->getRequest()->getPost('country')) {
            $countryCode = $this->getRequest()->getPost('country');
        } elseif (Mage::getSingleton('core/session')->getCountryCode()) {
            $countryCode = Mage::getSingleton('core/session')->getCountryCode();
        } else {
            $countryCode = Mage::getStoreConfig('general/country/default');
        }

        $countryCode = strtoupper($countryCode);

        $countries = Mage::helper('whereamip')->getActiveCountries();

        // Make sure the request is set in the array
        if (array_key_exists($countryCode, $countries)) {
            $storeCode = $countries[$countryCode]['code'];
            Mage::getSingleton('core/session')->setCountryCode($countryCode);
            Mage::getSingleton('core/session')->setStoreCode($storeCode);
            setcookie("store", $storeCode, time() + 60*60*24*30, "/");

            if ($this->getRequest()->getPost('ref')) {
                $store = Mage::app()->getStore($storeCode);

                if ($store) {
                    $url = $store->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);
                } else {
                    $url = $this->getRequest()->getPost('ref');
                }

                header("Location: " . $url);
                exit;
            }
        } else {
            Mage::getSingleton('core/session')->setError(
                sprintf($this->__('Country not found: %s.'), $countryCode)
            );
        }

        $this->loadLayout();
        $this->getLayout()->getBlock('changeCountry');
        $this->renderLayout();
    }
}
