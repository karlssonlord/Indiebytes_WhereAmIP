<?php
/**
 * Where Am IP
 * Copyright (C) 2013 Indiebytes
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category   Indiebytes
 * @package    Indiebytes_WhereAmIP
 * @subpackage Indiebytes_WhereAmIP_Helper
 * @author     Robert Lord <robert@karlssonlord.com>
 * @author     Andreas Karlsson <andreas@karlssonlord.com>
 * @author     Erik Eng <erik@karlssonlord.com>
 * @copyright  2013 Indiebytes
 * @license    LGPL v2.1 http://choosealicense.com/licenses/lgpl-v2.1/
 * @link       https://github.com/indiebytes/Indiebytes_WhereAmIP
 */

/**
 * General data helper
 *
 * @category   Indiebytes
 * @package    Indiebytes_WhereAmIP
 * @subpackage Indiebytes_WhereAmIP_Helper
 * @author     Robert Lord <robert@karlssonlord.com>
 * @author     Andreas Karlsson <andreas@karlssonlord.com>
 * @author     Erik Eng <erik@karlssonlord.com>
 * @copyright  2013 Indiebytes
 * @license    LGPL v2.1 http://choosealicense.com/licenses/lgpl-v2.1/
 * @link       https://github.com/indiebytes/Indiebytes_WhereAmIP
 */
class Indiebytes_WhereAmIP_Helper_Data extends Mage_Core_Helper_Abstract
{
    private $_selectableCountries;

    /**
     * Get URL where we should redirect
     *
     * @return string|boolean
     */
    public function getRedirectUrl()
    {
        $storeCode  = Mage::getSingleton('core/session')->getStoreCode();
        $storeUrl   = Mage::getModel('core/store')->load($storeCode)->getBaseUrl();
        $storeId    = Mage::getModel('core/store')->load($storeCode)->getId();

        $currentUrl = Mage::helper('core/url')->getCurrentUrl();
        $path       = basename($currentUrl);

        $return     = false;

        /**
         * Make sure we got a store code
         */
        if(is_null($storeCode)) {
            return $return;
        }

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
             * Fetch current request URI
             */
            if (isset($_SERVER['REQUEST_URI'])) {
                $requestUri = $_SERVER['REQUEST_URI'];
            } else {
                $requestUri = '/';
            }

            /**
             * Remove store codes from request URI
             */
            foreach (Mage::app()->getStores() as $_eachStoreId => $val) {
                /**
                 * This item in loop
                 */
                $_storeCode = Mage::app()->getStore($_eachStoreId)->getCode();
                $_storeUrl  = Mage::getModel('core/store')->load($storeCode)->getBaseUrl();

                $_path       = substr($currentUrl, strlen($storeUrl), strrpos($currentUrl, $path) + strlen($path));

                /**
                 * Remove trailing slashes
                 */
                if (substr($_path, -1) == '/') {
                    $_path = substr($_path, 0, (strlen($_path) - 1));
                }

                if ($_path) {
                    $rewriteFrom = Mage::getModel('core/url_rewrite')
                        ->setStoreId($_eachStoreId)
                        ->loadByRequestPath($path);

                    $rewriteTo = Mage::getModel('core/url_rewrite')
                        ->setStoreId($storeId)
                        ->loadByIdPath($rewriteFrom->getIdPath());

                    if ($rewriteFrom->getRequestPath() && $rewriteTo->getRequestPath()) {
                        if (substr($storeUrl, -1) != '/') {
                            $storeUrl .= '/';
                        }
                        $url = $storeUrl . $rewriteTo->getRequestPath();

                        return $url;
                    }
                }

                if (strlen($requestUri) == (strlen('/' . $_storeCode . '/'))) {
                    /**
                     * Change URL path
                     */
                    $requestUri = substr($requestUri, strlen('/' . $_storeCode), strlen($requestUri));

                } else if (substr($requestUri, 0, (strlen('/' . $_storeCode . '/'))) == '/' . $_storeCode . '/') {
                    /**
                     * Change URL path
                     */
                    $requestUri = substr($requestUri, strlen('/' . $_storeCode), strlen($requestUri));
                }

            }

            /**
             * Set the redirect URL
             */
            $return = $storeUrl . $requestUri;
        }
        return $return;
    }

    /**
     * Get active countries
     *
     * @return array
     */
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

    /**
     * Get URL
     *
     * @return string
     */
    function getUrl()
    {
        return Mage::getUrl('whereamip');
    }

    /**
     * /dev/null
     *
     * @todo Remove
     *
     * @return Indiebytes_WhereAmIP_Helper_Data
     */
    public function log()
    {
        return $this;
    }

    /**
     * Get current country name
     *
     * @return string
     */
    public function getCurrentCountryName()
    {
        $countryCode = Mage::getSingleton('core/session')->getCountryCode();
        return Mage::getModel('core/locale')->getCountryTranslation($countryCode);
    }
}
