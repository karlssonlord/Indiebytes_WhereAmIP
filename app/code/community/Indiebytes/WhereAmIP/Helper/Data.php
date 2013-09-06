<?php
class Indiebytes_WhereAmIP_Helper_Data extends Mage_Core_Helper_Abstract
{
    private $_selectableCountries;

    /**
     * Get URL where we should redirect
     *
     * @author Robert Lord <robert@karlssonlord.com>
     *
     * @return string|boolean
     */
    public function getRedirectUrl()
    {
        /**
         * Setup the default return result
         */
        $return = FALSE;

        /**
         * Fetch current store code
         */
        $storeCode = Mage::getSingleton('core/session')->getStoreCode();

        /**
         * Fetch current URL
         */
        $currentUrl = Mage::helper('core/url')->getCurrentUrl();

        /**
         * Fetch URL for store code
         */
        $storeUrl = Mage::getModel('core/store')->load($storeCode)->getUrl();

        /**
         * Make sure the URL's has trailing / when comparing
         */
        if (substr($storeUrl, -1) !== '/') {
            $storeUrl .= '/';
        }

        if (substr($currentUrl, -1) !== '/') {
            $currentUrl .= '/';
        }

        /**
         * Compare the URLs
         */
        if (substr($currentUrl, 0, (strlen($storeUrl))) !== $storeUrl) {
            /**
             * Remove trailing slashes
             */
            if (substr($storeUrl, -1) == '/') {
                $storeUrl = substr($storeUrl, 0, (strlen($storeUrl) - 1));
            }

            /**
             * Set the redirect URL
             */
            $return = $storeUrl . $_SERVER['REQUEST_URI'];
        }

        return $return;
    }

    public function getActiveCountries()
    {
        if ( $this->_selectableCountries ) {
            return $this->_selectableCountries;
        }

        $return = array();
        $returnSort = array();
        $countryModel = Mage::getModel('directory/country');

        // Get all websites
        foreach (Mage::app()->getWebsites() as $website) {
            // Get all groups
            foreach ($website->getGroups() as $group) {
                // Get all stores
                foreach ($group->getStores() as $store) {
                    // Get allowed countries list
                    $countryList = Mage::getStoreConfig('general/country/allow', $store->getId());
                    // Explode the values into an array
                    $countryList = explode(',', $countryList);
                    // Add them to our array
                    foreach ( $countryList as $country ) {
                        if ( !isset($return[$country]) ) {
                            $return[$country] = array(
                                'store_id' => $store->getId(),
                                'country' => $countryModel->loadByCode($country)->getName(),
                                'code' => $store->getCode(),
                                'currency' => $store->getCurrentCurrencyCode(),
                                'origin' => $store->getConfig('shipping/origin/country_id')
                            );
                            // Add to the sort array
                            $returnSort[$return[$country]['country']][] = $country;
                        }
                    }
                }
            }
        }

        ksort($returnSort);

        // Set up the final result array
        $result = array();

        // Loop through the sorted array
        foreach ( $returnSort as $countryCodeMaster => $countries ) {
            foreach ( $countries as $countryCode ) {
                $result[$countryCode] = $return[$countryCode];
            }
        }

        $this->_selectableCountries = $result;

        return $return;
    }

    function getUrl() {
        return Mage::getUrl('whereamip');
    }

    /**
     * Erik fixed this too
     *
     */
    public function log()
    {
        return $this; // Robban?!
    }
}
