<?php
/**
 * Ffuenf_TrustedShopsStars extension.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT License
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/mit-license.php
 *
 * @category   Ffuenf
 *
 * @author     Achim Rosenhagen <a.rosenhagen@ffuenf.de>
 * @copyright  Copyright (c) 2016 ffuenf (http://www.ffuenf.de)
 * @license    http://opensource.org/licenses/mit-license.php MIT License
 */

class Ffuenf_TrustedShopsStars_Model_Cron
{
    const XML_PATH_EXTENSION_API_TSID         = 'ffuenf_trustedshopsstars/api/tsId';
    const XML_PATH_EXTENSION_API_CACHETIMEOUT = 'ffuenf_trustedshopsstars/api/cacheTimeOut';
    const XML_PATH_EXTENSION_DATA_RATINGVALUE = 'ffuenf_trustedshopsstars/data/ratingValue';
    const XML_PATH_EXTENSION_DATA_BESTRATING  = 'ffuenf_trustedshopsstars/data/bestRating';
    const XML_PATH_EXTENSION_DATA_RATINGCOUNT = 'ffuenf_trustedshopsstars/data/ratingCount';

    protected function _trustedShopsCacheCheck($cachePath)
    {
        if (file_exists($cachePath) && time() - filemtime($cachePath) < Mage::getStoreConfig(self::XML_PATH_EXTENSION_API_CACHETIMEOUT)) {
            return true;
        }
        return false;
    }

    /**
     * Get trustedshops rating from api
     *
     * @return null|array<String>
     */
    public function getRating()
    {
        if (!Mage::helper('ffuenf_trustedshopsstars')->isExtensionActive()) {
            return;
        }
        $tsId = Mage::getStoreConfig(self::XML_PATH_EXTENSION_API_TSID);
        $cachePath = Mage::getBaseDir('var') . DS . Mage::getStoreConfig(self::XML_PATH_EXTENSION_API_TSID) . '.json';
        $cacheTimeOut = Mage::getStoreConfig(self::XML_PATH_EXTENSION_API_CACHETIMEOUT);
        $apiUrl = 'http://api.trustedshops.com/rest/public/v2/shops/' . $tsId . '/quality/reviews.json';
        $reviewsFound = false;
        $data = NULL;
        if (!$this->_trustedShopsCacheCheck($cachePath)) {
            $ch = curl_init(); 
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, false);
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            $output = curl_exec($ch);
            curl_close($ch);
            Ffuenf_Common_Model_Logger::logSystem(
                array(
                    'class'   => 'Ffuenf_TrustedShopsStars',
                    'type'    => Zend_Log::DEBUG,
                    'message' => 'called trustedshops api at ' . $apiUrl,
                    'details' => $output
                )
            );
            file_put_contents($cachePath, $output);
        }
        if ($jsonObject = json_decode(file_get_contents($cachePath), true)) {
            $result = $jsonObject['response']['data']['shop']['qualityIndicators']['reviewIndicator']['overallMark'];
            $count = $jsonObject['response']['data'] ['shop']['qualityIndicators']['reviewIndicator']['activeReviewCount'];
            $shopName = $jsonObject['response']['data']['shop']['name'];
            $max = '5.00';
            if ($count > 0) {
                $reviewsFound = true;
            }
        }
        if ($reviewsFound) {
            $installer = Mage::getResourceModel('catalog/setup','catalog_setup');
            $installer->setConfigData(self::XML_PATH_EXTENSION_DATA_RATINGVALUE, $result, 'default', 0);
            $installer->setConfigData(self::XML_PATH_EXTENSION_DATA_BESTRATING, $max, 'default', 0);
            $installer->setConfigData(self::XML_PATH_EXTENSION_DATA_RATINGCOUNT, $count, 'default', 0);
            Ffuenf_Common_Model_Logger::logSystem(
                array(
                    'class'   => 'Ffuenf_TrustedShopsStars',
                    'type'    => Zend_Log::DEBUG,
                    'message' => 'updated trustedshops data',
                    'details' => 'ratingValue: ' . $result . '<br />' . 'bestRating: ' . $max . '<br />' . 'ratingCount: ' . $count
                )
            );
        }
        return $this;
    }
}
