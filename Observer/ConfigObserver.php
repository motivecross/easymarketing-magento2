<?php

namespace Motive\Easymarketing\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Motive\Easymarketing\Helper\Data;

class ConfigObserver implements ObserverInterface
{
    protected $_helper;

    protected $_storeManager;

    protected $_apiUrl = 'https://api.easymarketing.de/configure_endpoints';

    public function __construct(
        Data $helper,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->_helper = $helper;
        $this->_storeManager = $storeManager;
    }

    public function execute(EventObserver $observer) {
        if(!$this->_helper->getConfig('easymarketingsection/easmarketinggeneral/enable')) {
            return;
        }

        $this->_helper->log('Call configure_endpoints START');

        $storeId = $this->_helper->getParam('store');

        $baseUrl = $this->_storeManager->getStore($storeId)->getBaseUrl();
        $paramsArray['website_url'] = parse_url($baseUrl, PHP_URL_HOST);

        $paramsArray['access_token'] = $this->_helper->getConfig('easymarketingsection/easmarketinggeneral/access_token', $storeId);

        $paramsArray['shop_token'] = $this->_helper->getConfig('easymarketingsection/easmarketinggeneral/shop_token', $storeId);

        $rootCategory = $this->_helper->getConfig('easymarketingsection/easmarketinggeneral/rootcategory');
        if(empty($rootCategory)) $rootCategory = 1;
        $paramsArray['shop_category_root_id'] = $rootCategory;

        $paramsArray['categories_api_endpoint'] = $baseUrl . 'easymarketing/api/categories';

        $paramsArray['products_api_endpoint'] = $baseUrl . 'easymarketing/api/products';

        $accessToken = $this->_helper->getConfig('easymarketingsection/easmarketinggeneral/access_token');
        //$accessToken = '';

        $result = $this->_helper->emserviceCall($this->_apiUrl . '?access_token=' . $accessToken, $paramsArray);

        if($result['http_status'] == '401') {
            $this->_helper->log('Wrong Access Token');
            $this->_helper->dbUpdateOne("configuration_status", 0);
            $this->_helper->dbUpdateOne('configuration_last_errors', 'Configure Endpoints: Wrong Access Token');

        } elseif($result['http_status'] == '200') {
            $this->_helper->log($result['content']);
            $this->_helper->dbUpdateOne("configuration_status", 1);

        } elseif($result['http_status'] == '400') {
            $resultArray = json_decode($result['content'], true);

            $this->_helper->log($result['content']);
            $this->_helper->dbUpdateOne("configuration_status", 0);
            $this->_helper->dbUpdateOne('configuration_last_errors', implode(', ', $resultArray['errors']));

        } else {
            $this->_helper->log('Unknown HTTP Response');
        }

        $this->_helper->log('Call configure_endpoints END');
    }
}