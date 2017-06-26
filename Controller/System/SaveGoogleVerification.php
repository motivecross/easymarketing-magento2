<?php

namespace Motive\Easymarketing\Controller\System;

use Motive\Easymarketing\Helper\Data;

class SaveGoogleVerification extends \Magento\Framework\App\Action\Action
{
    protected $_helper;

    protected $_storeManager;

    protected $_cacheFrontendPool;

    protected $_getUrl = 'https://api.easymarketing.de/site_verification_data';

    protected $_performUrl = 'https://api.easymarketing.de/perform_site_verification';

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        Data $helper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool
    ) {
        $this->_helper = $helper;
        $this->_storeManager = $storeManager;
        $this->_cacheFrontendPool = $cacheFrontendPool;
        return parent::__construct($context);
    }

    public function execute() {
        $this->_helper->log('Call google_verification START');

        if($this->_helper->dbFetchOne('google_verification_enable')) {
            $this->turnOff();
        } else {
            $this->turnOn();
        }

        $this->_helper->log('Call google_verification END');
    }

    protected function turnOff() {
        $this->_helper->dbUpdateOne("google_verification_status", 0);
        $this->_helper->dbUpdateOne("google_verification_enable", 0);

        foreach ($this->_cacheFrontendPool as $cacheFrontend) {
            $cacheFrontend->clean();
        }

        $this->_helper->sendResponse(array('status' => 3));
    }

    protected function turnOn() {
        if(!$accessToken = $this->_helper->emservicecallStart()) {
            $this->_helper->sendResponse(array('status' => 0));
        }

        $storeId = $this->_helper->getParam('store');

        $baseUrl = $this->_storeManager->getStore($storeId)->getBaseUrl();

        $result = $this->_helper->emserviceCall($this->_getUrl . '?access_token=' . $accessToken . '&website_url=' . parse_url($baseUrl, PHP_URL_HOST));

        $this->_helper->log($result['content']);

        if($result['http_status'] == '401') {
            $this->_helper->sendResponse(array('status' => 0));

        } elseif($result['http_status'] == '200') {
            $resultArray = json_decode($result['content'], true);
            $this->_helper->dbUpdateOne("google_verification_meta", $resultArray['meta_tag']);
            $this->_helper->dbUpdateOne("google_verification_enable", 1);

            foreach ($this->_cacheFrontendPool as $cacheFrontend) {
                $cacheFrontend->clean();
            }

            $paramsArray = array('website_url' => parse_url($baseUrl, PHP_URL_HOST), 'verification_type' => 'META');

            $result2 = $this->_helper->emserviceCall($this->_performUrl . '?access_token=' . $accessToken, $paramsArray);

            $this->_helper->log($result2['content']);

            if($result2['http_status'] == '401') {
                $this->_helper->sendResponse(array('status' => 0));

            } elseif($result2['http_status'] == '200') {
                $this->_helper->dbUpdateOne("google_verification_status", 1);

                $this->_helper->sendResponse(array('status' => 2));

            } elseif($result2['http_status'] == '400') {
                $this->_helper->dbUpdateOne("google_verification_status", 0);

                $resultArray2 = json_decode($result2['content'], true);

                $this->_helper->sendResponse(array('status' => 1, 'errors' => $resultArray2['errors']));

            } else {
                $this->_helper->log('Unknown HTTP Response');
                $this->_helper->sendResponse(array('status' => 0));
            }

        } elseif($result['http_status'] == '400') {
            $resultArray = json_decode($result['content'], true);

            $this->_helper->sendResponse(array('status' => 1, 'errors' => $resultArray['errors']));

        } else {
            $this->_helper->log('Unknown HTTP Response');
            $this->_helper->sendResponse(array('status' => 0));
        }
    }
}

