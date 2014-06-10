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

        /**
         * Make sure we have a store code available
         * for the visitor
         */
        if (is_null($storeCode)) {
            return $return;
        }

        /**
         * Fetch current request URI
         */
        if (isset($_SERVER['REQUEST_URI'])) {
            $requestUri = $_SERVER['REQUEST_URI'];
        } else {
            $requestUri = '/';
        }

        $store      = Mage::getModel('core/store')->load($storeCode);
        $storeId    = $store->getId();
        $storeUrl   = $this->appendSlash($store->getBaseUrl());
        $currentUrl = $this->appendSlash(Mage::helper('core/url')->getCurrentUrl());
        $path       = basename($currentUrl);

        /**
         * Compare the request URL with the store URL to see if the request belongs to
         * the current store scope
         */
        if (substr($currentUrl, 0, strlen($storeUrl)) !== $storeUrl) {

            $storeUrl = $this->stripSlash($storeUrl);

            /**
             * Iterate all stores
             */
            foreach (Mage::app()->getStores() as $_eachStoreId => $val) {
                $_storeCode = Mage::app()->getStore($_eachStoreId)->getCode();
                $_storeUrl  = Mage::getModel('core/store')->load($storeCode)->getBaseUrl();
                $_path      = $this->stripSlash(
                    substr(
                        $currentUrl,
                        strlen($storeUrl),
                        strrpos($currentUrl, $path) + strlen($path)
                    )
                );

                /**
                 * Look at the URL Rewrites to see if there is a redirect available
                 * for the request. This is really nice to have if you work with
                 * localized slugs in your catalog.
                 */
                if ($_path) {
                    $rewriteFrom = Mage::getModel('core/url_rewrite')
                        ->setStoreId($_eachStoreId)
                        ->loadByRequestPath($_path);

                    $rewriteTo = Mage::getModel('core/url_rewrite')
                        ->setStoreId($storeId)
                        ->loadByIdPath($rewriteFrom->getIdPath());

                    if ($rewriteFrom->getRequestPath() && $rewriteTo->getRequestPath()) {
                        $url = $this->appendSlash($storeUrl) . $rewriteTo->getRequestPath();

                        return $url;
                    }
                }

                /**
                 * /storecode/ or /storcode
                 */
                if (str_replace('/', '', $requestUri) == $_storeCode) {
                    $requestUri = '/';

                    return $storeUrl . $requestUri;

                /**
                 * /storecode/slug or /storcode/slug/
                 */
                } else if (substr($requestUri, 0, strlen('/' . $_storeCode . '/')) == '/' . $_storeCode . '/') {
                    $requestUri = substr($requestUri, strlen('/' . $_storeCode), strlen($requestUri));

                    return $storeUrl . $requestUri;
                }
            }
        }

        /**
         * Fallbacks if no store code was found in the request
         */
        if (substr($requestUri, 0, strlen('/' . $storeCode . '/')) == '/' . $storeCode . '/' ||
            substr($requestUri, 0, strlen('/' . $storeCode)) == '/' . $storeCode) {
            return false;
        } else {
            return $storeUrl . $requestUri;
        }
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
                                'origin' => $countryModel->loadByCode($store->getConfig('shipping/origin/country_id'))->getName()
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

        return $result;
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

    /**
     * Get current country code with fallback if country isn't active
     *
     * @return string
     */
    public function getCurrentCountryCodeWithFallback()
    {
        $activeCountries = $this->getActiveCountries();
        $countryCode = Mage::getSingleton('core/session')->getCountryCode();
        if (!isset($activeCountries[$countryCode])) {
            $countryCode = Mage::getStoreConfig('general/country/default');
        }

        return $countryCode;
    }

    /**
     * Get current country name with fallback if country isn't active
     *
     * @return string
     */
    public function getCurrentCountryNameWithFallback()
    {
        $countryCode = $this->getCurrentCountryCodeWithFallback();

        return Mage::getModel('core/locale')->getCountryTranslation($countryCode);
    }

    /**
     * Append slash
     *
     * @return string
     */
    public function appendSlash($string)
    {
        if (substr($string, -1) !== '/') {
            $string .= '/';
        }

        return $string;
    }

    /**
     * Strip slash
     *
     * @return string
     */
    public function stripSlash($string)
    {
        if (substr($string, -1) === '/') {
            $string = substr($string, 0, (strlen($string) - 1));
        }

        return $string;
    }
}
