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
         * Shortcut to our helper
         */
        $helper = Mage::helper('whereamip');

        /**
         * Fetch current IP
         */
        $ip = isset($_GET['ip']) ? $_GET['ip'] : Mage::helper('core/http')->getRemoteAddr(false);

        /*
        $ip = '192.30.252.128'; // Github
        $ip = '2.136.0.0'; // Spanien
        $ip = '37.16.96.0'; // Finland
        */

        /**
         * Log the IP address found
         */
        $helper->log('Fetched IP address ' . $ip);

        /**
         * Check if session data exists
         */
        if (!Mage::getSingleton('core/session')->getStoreCode() && $ip !== null) {
            /**
             * No session or IP was found
             */
            $helper->log('No session/ip was found');

            /**
             * Get active countries
             */
            $countries = Mage::helper('whereamip')->getActiveCountries();

            /**
             * Set GeoIP as geo instance
             *
             * @todo explain
             */
            Mage::helper('ugeoip')->getGeoInstance('GeoIP');

            /**
             * getGeoLocation
             *
             * @todo explain
             */
            $geoIp = Mage::helper('ugeoip')->getGeoLocation(true, $ip);

            /**
             * Load the country code
             */
            $geoCountryCode = $geoIp->getData('countryCode') ? $geoIp->getData('countryCode') : Mage::getStoreConfig('general/country/default');

            Mage::getSingleton('core/session')->setCountryCode($geoCountryCode);

            if (array_key_exists($geoCountryCode, $countries)) {
                $storeCode = $countries[$geoCountryCode]['code'];
                Mage::getSingleton('core/session')->setStoreCode($storeCode);

                $setCookieResult = setcookie("store", $storeCode, time() + 60*60*24*30, "/");

                /**
                 * If it's the intranet calling, don't do any JS redirect, just pass it along
                 * 91.223.232.187 = SERVER_IP
                 */
                if ($_SERVER['REMOTE_ADDR'] == '91.223.232.187') {
                    $setCookieResult = false;
                }

                /**
                 * If cookie was saved, redirect to same page
                 */
                if ($setCookieResult && 1 == 2) {
                    /**
                     * Redirect
                     */
                    $redirectUrl = Mage::helper('core/url')->getCurrentUrl();
                    ?>
                    <script type="text/javascript">
                        function createCookie(name, value, days) {
                            var expires;
                            if (days) {
                                var date = new Date();
                                date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                                expires = "; expires=" + date.toGMTString();
                            }
                            else expires = "";
                            document.cookie = name + "=" + value + expires + "; path=/";
                        }

                        function readCookie(name) {
                            var nameEQ = name + "=";
                            var ca = document.cookie.split(';');
                            for (var i = 0; i < ca.length; i++) {
                                var c = ca[i];
                                while (c.charAt(0) == ' ') c = c.substring(1, c.length);
                                if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
                            }
                            return null;
                        }

                        function eraseCookie(name) {
                            createCookie(name, "", -1);
                        }

                        function areCookiesEnabled() {
                            var r = false;
                            createCookie("testing", "Hello", 1);
                            if (readCookie("testing") != null) {
                                r = true;
                                eraseCookie("testing");
                            }
                            return r;
                        }
                        if (areCookiesEnabled() == false) {
                            alert("no");
                            top.location = '/enable-cookies.html';
                        } else {
                            top.location = '<?php print $redirectUrl; ?>';
                        }

                    </script>
                    <?php
                    exit;
                }
            }
        } else {
            $geoCountryCode = Mage::getSingleton('core/session')->getCountryCode();
            $storeCode      = Mage::getSingleton('core/session')->getStoreCode();

            setcookie("store", $storeCode, time() + 60*60*24*30, "/");
        }
    }
}
