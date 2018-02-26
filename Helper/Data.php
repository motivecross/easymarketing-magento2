<?php

#######
# Motive X
# Sylter Str. 15, 90425 Nürnberg, Germany
# Telefon: +49 (0)911/49 522 566
# Mail: info@motive.de
# Internet: www.motive-x.com
#######

namespace Motive\Easymarketing\Helper;

use Magento\Catalog\Model\Entity\AttributeFactory;
use \Magento\Framework\App\Helper\AbstractHelper;

class Data extends AbstractHelper
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    protected $_request;

    protected $_logger;

    protected $_resource;

    protected $_productAttributeRepository;

    protected $_attributesCollectionFactory;

    protected $_dbconnection;

    public $jsonParameters = JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Catalog\Model\Product\Attribute\Repository $productAttributeRepository,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory $attributesCollectionFactory,
        \Motive\Easymarketing\Logger\Logger $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->_request = $request;
        $this->_resource = $resource;
        $this->_productAttributeRepository = $productAttributeRepository;
        $this->_attributesCollectionFactory = $attributesCollectionFactory;
        $this->_logger = $logger;

        $this->_dbconnection = $this->_resource->getConnection(\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION);
    }

    protected function checkShoptoken() {
        $shopToken = $this->getParam('shop_token');
        if(!empty($shopToken) && $shopToken == $this->getConfig('easymarketingsection/easmarketinggeneral/shop_token')) {
            return true;
        } else {
            return false;
        }
    }

    public function apiStart() {

        if(!$this->getConfig('easymarketingsection/easmarketinggeneral/enable')) {
            $this->sendErrorAndExit('Module not activated');
        }

        if(!$this->checkShoptoken()) {
            $this->sendErrorAndExit('Wrong Shop Token');
        }
    }

    public function emservicecallStart() {

        $accessToken = $this->getConfig('easymarketingsection/easmarketinggeneral/access_token');

        if(empty($accessToken)) {
            return false;
        } else {
            return $accessToken;
        }
    }

    public function getConfig($configPath, $storeId = false) {
        if($storeId) {
            return $this->scopeConfig->getValue($configPath, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
        } else {
            return $this->scopeConfig->getValue($configPath,\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        }
    }

    public function getParam($param) {
        return $this->_request->getParam($param);
    }

    public function log($message) {
        $this->_logger->info($message);
    }

    public function error($message) {
        $this->_logger->error($message);
    }

    public function getAllMandatoryParams($params) {
        $returnArray = array();
        foreach($params as $param) {
            $value = $this->getParam($param);
            if(empty($value) && $value !== 0 && $value !== '0') {
                $this->sendErrorAndExit('Not enough parameters');
            } else {
                $returnArray[$param] = $value;
            }
        }
        return $returnArray;
    }

    public function sendResponse($result) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($result, $this->jsonParameters);
    }

    public function sendErrorAndExit($message) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
        echo $message;
        $this->_logger->error('ERROR: ' . $message);
        exit;
    }

    public function emserviceCall($url, $paramsArray = array()) {
        $data_string = "";
        
        $ch = curl_init($url);
        if(empty($paramsArray)) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        } else {
            $data_string = json_encode($paramsArray, $this->jsonParameters);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string))
        );

        $result = curl_exec($ch);

        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);
        return array('http_status' => $httpStatus, 'content' => $result);
    }

    public function dbFetchOne($field, $storeId = 0) {

        $fetch = $this->_dbconnection->fetchOne('SELECT data_value FROM `easymarketing_data` WHERE data_name = "' . $field . '"' . ' AND data_scope = "' . $storeId . '"');
        return $fetch;
    }

    public function dbUpdateOne($field, $value, $storeId = 0) {

        $updatedRows = $this->_dbconnection->update('easymarketing_data', array('data_value' => $value), 'data_name = "' . $field . '"');

        return $updatedRows;
    }

    public function getAttributeByCode($code) {
        $attribute = $this->_productAttributeRepository->get($code);

        return $attribute;
    }

    public function getAllAttributes() {
        $attributeCollection = $this->_attributesCollectionFactory->create();

        // add filter by entity type to get product attributes only
        // '4' is the default type ID for 'catalog_product' entity - see 'eav_entity_type' table)
        // or skip the next line to get all attributes for all types of entities
        $attributeCollection->addFieldToFilter(\Magento\Eav\Model\Entity\Attribute\Set::KEY_ENTITY_TYPE_ID, 4);
        $attributeCollection->addFieldToFilter('frontend_input', array('text', 'select', 'decimal', 'date', 'price', 'textarea', 'weight', 'multiselect'));
        $attributeCollection->setOrder('frontend_label', \Magento\Framework\Data\Collection::SORT_ORDER_ASC);
        $attributes = $attributeCollection->load()->getItems();

        $returnArray = array();
        foreach($attributes as $attribute) {
            if($attribute->getIsUserDefined()) {
                $returnArray[] = ['value' => $attribute->getAttributeCode(), 'label' => $attribute->getStoreLabel() . ' (' . $attribute->getAttributeCode() . ')'];
            }
        }

        return $returnArray;
    }
}