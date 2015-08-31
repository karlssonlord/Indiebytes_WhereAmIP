<?php
/**
 * Front Controller Observer
 *
 * @category Indiebytes
 * @package  Indiebytes_WhereAmIP
 * @author   Andreas Karlsson <andreas.karlsson@indiebytes.se>
 */
class Indiebytes_WhereAmIP_Model_FrontControllerObserver
{

    /**
     * getCountryCode
     *
     * @return void
     */
    public function getCountryCode()
    {
        /**
         * If called from command line, don't use these cookies
         */
        if (function_exists('php_sapi_name')) {
            if (php_sapi_name() == 'cli') {
                return;
            }
        }

        /**
         * Get current IP
         *
         * You can easily pass a GET-variable containing an IP address to easier
         * debug the code.
         **/
        $customerIp = isset($_GET['ip']) ? $_GET['ip'] : Mage::helper('core/http')->getRemoteAddr(false);
        $websiteCountries = Mage::helper('whereamip')->getActiveCountriesByWebsite();
        $storeCode = Mage::getSingleton('core/session')->getStoreCode();

        /**
         * Set store code and country code to session
         *
         * If store code is missing in the session and if we successfully
         * have received an IP address, then we should try to set the store
         * code based on active countries.
         **/
        if ((!$storeCode || !array_key_exists(strtoupper($storeCode), $websiteCountries)) && $customerIp !== null) {
            /**
             * Get active countries
             **/
            $countries = Mage::helper('whereamip')->getActiveCountries();

            /**
             * Set GeoIP as geo instance
             **/
            Mage::helper('ugeoip')->getGeoInstance('GeoIP');

            /**
             * getGeoLocation
             */
            if (filter_var($customerIp, FILTER_VALIDATE_IP)) {
                try {
                    $geoIp = Mage::helper('ugeoip')->getGeoLocation(true, $customerIp);
                } catch (Except $e) {
                    $geoIp = new Varien_Object();
                }
            } else {
                $geoIp = new Varien_Object();
            }

            /**
             * Load the country code and save it to session
             **/
            $geoCountryCode = $geoIp->getData('countryCode') ?
                $geoIp->getData('countryCode') : Mage::getStoreConfig('general/country/default');
            Mage::getSingleton('core/session')->setCountryCode($geoCountryCode);

            if (array_key_exists($geoCountryCode, $websiteCountries)) {
                $storeCode = $countries[$geoCountryCode]['code'];
                Mage::getSingleton('core/session')->setStoreCode($storeCode);
                setcookie("store", $storeCode, time() + 60*60*24*30, "/");
            }
        } else {
            $geoCountryCode = Mage::getSingleton('core/session')->getCountryCode();
            $storeCode      = Mage::getSingleton('core/session')->getStoreCode();

            setcookie("store", $storeCode, time() + 60*60*24*30, "/");
        }
    }
}
