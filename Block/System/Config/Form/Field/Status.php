<?php

namespace Motive\Easymarketing\Block\System\Config\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Motive\Easymarketing\Helper\Data;

class Status extends Field
{
    protected $_helper;

    protected $_storeManager;

    protected $_extractionUrl = 'https://api.easymarketing.de/extraction_status';

    public function __construct(
        Context $context,
        Data $helper,
        array $data = []
    ) {
        $this->_helper = $helper;
        $this->_storeManager = $context->getStoreManager();
        parent::__construct($context, $data);
    }

    protected function _getElementHtml(AbstractElement $element) {
        $html = '<div style="margin-top: 6px;">';

        if(!$this->_helper->getConfig('easymarketingsection/easmarketinggeneral/enable')) {
            $html .= "Modul nicht aktiviert";
        } else {
            $currentStatus = $this->_helper->dbFetchOne('configuration_status');

            if($currentStatus == 0) {
                $lastErrors = $this->_helper->dbFetchOne('configuration_last_errors');
                if(empty($lastErrors)) {
                    $html .= 'Konfiguration fehlerhaft.';
                } else {
                    $html .= '- ' . str_replace(', ', '<br>- ', $lastErrors);
                }
            } else {
                $this->_helper->log('Call extraction_status START');

                $accessToken = $this->_helper->getConfig('easymarketingsection/easmarketinggeneral/access_token');

                $storeId = $this->_helper->getParam('store');
                $baseUrl = $this->_storeManager->getStore($storeId)->getBaseUrl();

                $result = $this->_helper->emserviceCall($this->_extractionUrl . '?access_token=' . $accessToken . '&website_url=' . parse_url($baseUrl, PHP_URL_HOST));

                if($result['http_status'] == '401') {
                    $this->_helper->log('Wrong Access Token');
                    $html .= 'Wrong Access Token';

                } elseif($result['http_status'] == '200') {
                    $this->_helper->log($result['content']);
                    $resultArray = json_decode($result['content'], true);
                    if($resultArray['api_properly_setup'] > 1) {
                        $html .= 'Erfolgreich eingerichtet.';
                        $html .= '<br>Kategorien indexiert: ' . $resultArray['num_categories'];
                        $html .= '<br>Produkte indexiert: ' . $resultArray['num_products'];
                        $html .= '<br>Letzte Indexierung: ' . date('d.m.Y H:i:s', $resultArray['updated_at']);
                    }
                } elseif($result['http_status'] == '400') {
                    $resultArray = json_decode($result['content'], true);
                    $html .= implode(', ', $resultArray['errors']);
                    $this->_helper->log($result['content']);

                } else {
                    $this->_helper->log('Unknown HTTP Response');
                }

                $this->_helper->log('Call extraction_status END');
            }
        }

        $html .= '</div>';
        return $html;
    }
}