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
    function getCountryCode() {
        $ip = isset($_GET['ip']) ? $_GET['ip'] : Mage::helper('core/http')->getRemoteAddr(false);

        $countries = Mage::helper('whereamip')->getActiveCountries();

        if (!Mage::getSingleton('core/session')->getStoreCode() && $ip !== null) {
            Mage::helper('ugeoip')->getGeoInstance('GeoIP');
            $geoIp = Mage::helper('ugeoip')->getGeoLocation(true, $ip);
            $geoCountryCode = $geoIp->getData('countryCode') ? $geoIp->getData('countryCode') : Mage::getStoreConfig('general/country/default');;

            Mage::getSingleton('core/session')->setCountryCode($geoCountryCode);

            if (array_key_exists($geoCountryCode, $countries)) {
                $storeCode = $countries[$geoCountryCode]['code'];
                Mage::getSingleton('core/session')->setStoreCode($storeCode);

                setcookie("store", $storeCode, time() + 60*60*24*30, "/");
            }
        } else {
            $geoCountryCode = Mage::getSingleton('core/session')->getCountryCode();
            $storeCode = Mage::getSingleton('core/session')->getStoreCode();

            setcookie("store", $storeCode, time() + 60*60*24*30, "/");
        }
    }
}
