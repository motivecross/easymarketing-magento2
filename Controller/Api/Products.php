<?php

#######
# Motive X
# Sylter Str. 15, 90425 Nürnberg, Germany
# Telefon: +49 (0)911/49 522 566
# Mail: info@motive.de
# Internet: www.motive-x.com
#######

namespace Motive\Easymarketing\Controller\Api;

use Motive\Easymarketing\Helper\Data;

class Products extends \Magento\Framework\App\Action\Action
{
    protected $_helper;

    protected $_productCollectionFactory;

    protected $_stockRegistry;

    protected $_storeManager;

    protected $_shipConfig;

    protected $_quoteFactory;

    protected $_catalogProductTypeConfigurable;

    protected $_productFactory;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        Data $helper,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Shipping\Model\Config $shipConfig,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $catalogProductTypeConfigurable,
        \Magento\Catalog\Model\ProductFactory $productFactory
    ) {
        $this->_helper = $helper;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_stockRegistry = $stockRegistry;
        $this->_storeManager = $storeManager;
        $this->_shipConfig = $shipConfig;
        $this->_quoteFactory = $quoteFactory;
        $this->_catalogProductTypeConfigurable = $catalogProductTypeConfigurable;
        $this->_productFactory = $productFactory;
        return parent::__construct($context);
    }

    public function execute() {

        $this->_helper->log('Products Endpoint START');

        try {
            $this->_helper->apiStart();

            $params = $this->_helper->getAllMandatoryParams(array('offset', 'limit'));

            $offset = $limit = 0;

            $collection = $this->_productCollectionFactory->create();
            $collection->setOrder('id', 'ASC');
            $collection->addAttributeToSelect('*');
            $collection->addAttributeToFilter('status', '1');
            $collection->addAttributeToFilter('type_id', array('neq' => 'bundle'));
            $collection->addAttributeToFilter('type_id', array('neq' => 'configurable'));
            $collection->addAttributeToFilter('type_id', array('neq' => 'grouped'));

            if(is_numeric($params['limit']) && $params['limit'] > 0) {
                $limit = $params['limit'];
                if(is_numeric($params['offset']) && $params['offset'] > 0) {
                    $offset = $params['offset'];
                }

                $collection->getSelect()->limit($limit, $offset);
            }

            $productsArray = array();
            foreach($collection->getItems() as $item) {
                $product = array();
                $productId = $item->getId();
                $stockItem = $this->_stockRegistry->getStockItem($productId, $item->getStore()->getWebsiteId());

                $product['id'] = intval($productId);

                $parentIDs = $this->_catalogProductTypeConfigurable->getParentIdsByChild($productId);

                $name = $this->getMappedConfig('name', $item);
                if(empty($name)) {
                    $name = $item->getName();
                }
                $product['name'] = $name;

                if($this->_helper->getConfig('easymarketingsection/easymarketingassign/useshortdescription')) {
                    $product['description'] = $item->getShortDescription();
                } else {
                    $product['description'] = $item->getDescription();
                }

                $categoryIds = $item->getCategoryIds();
                if(empty($categoryIds) && !empty($parentIDs)) {
                    $categoryProduct = $this->_productFactory->create()->load($parentIDs[0]);
                    $categoryIds = $categoryProduct->getCategoryIds();
                }

                $product['categories'] = $categoryIds;

                $condition = $this->getMappedConfig('condition', $item);
                $conditionPossibilities = array('new', 'refurbished', 'used');
                if(in_array($condition, $conditionPossibilities)) {
                    $product['condition'] = $condition;
                } else {
                    $product['condition'] = 'new';
                }

                if($stockItem->getIsInStock()) {
                    $product['availability'] = 'in stock';
                } else {
                    $product['availability'] = 'not in stock';
                }

                $shippingProductId = 0;
                $price = $item->getPrice();
                if($item->getTypeId() == "configurable") {
                    $children = $item->getTypeInstance()->getUsedProducts($item);
                    $price = 9999999;
                    foreach ($children as $child){
                        if($child->getPrice() < $price) {
                            $price = $child->getPrice();
                            $shippingProductId = $child->getId();
                            $stockItem = $this->_stockRegistry->getStockItem($shippingProductId, $child->getStore()->getWebsiteId());
                        }
                    }
                }

                $quantity = $stockItem->getQty();
                if(empty($quantity) && $quantity !== '0') {
                    $product['stock_quantity'] = null;
                } else {
                    $product['stock_quantity'] = intval($quantity);
                }

                $product['price'] = floatval($price);

                if($item->getVisibility() == 1 && !empty($parentIDs)) {
                    $urlProduct = $this->_productFactory->create()->load($parentIDs[0]);
                    $product['url'] = $urlProduct->getProductUrl();
                } else {
                    $product['url'] = $item->getProductUrl();
                }

                $product['image_url'] = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $item->getImage();
                $product['currency'] = $this->_storeManager->getStore()->getCurrentCurrencyCode();

                $shippingArray = array();
                try {
                    /*$tmpInStock = $stockItem->getIsInStock();
                    if(!$tmpInStock) {
                        $tmpQty = $stockItem->getQty();
                        if($tmpQty < 1) {
                            $stockItem->setQty(1);
                        }
                        $stockItem->setIsInStock(1);
                        $stockItem->save();
                    }*/
                    $countryConf = $this->_helper->getConfig('easymarketingsection/easmarketinggeneral/shipping_countries');
                    if(empty($countryConf)) {
                        $countryConf = 'DE';
                    }
                    $countryArray = explode(",", $countryConf);
                    foreach($countryArray as $country) {
                        $quote = $this->_quoteFactory->create();
                        if($shippingProductId != 0) {
                            $quoteProduct = $this->_productFactory->create()->load($shippingProductId);
                        } else {
                            $quoteProduct = $item;
                        }
                        $quote->addProduct($quoteProduct);
                        $address = $quote->getShippingAddress();
                        $address->setCountryId($country);
                        $address->setCollectShippingRates(true);
                        $quote->setTotalsCollectedFlag(false)->collectTotals();
                        $address->collectShippingRates();

                        $rates = $address->getShippingRatesCollection();

                        $currentPrice = 9999999;
                        foreach($rates as $rate) {
                            $price = $rate->getData('price');
                            if($price < $currentPrice) {
                                $currentPrice = $price;
                            }
                        }
                        if($currentPrice == 9999999) {
                            continue;
                        } else {
                            $shippingArray[] = array('country' => $country, 'price' => floatval($currentPrice));
                        }
                    }
                    /*if(!$tmpInStock) {
                        if($tmpQty < 1) {
                            $stockItem->setQty(0);
                        }
                        $stockItem->setIsInStock(0);
                        $stockItem->save();
                    }*/
                } catch(\Exception $exception) {
                    $errorMessage = $exception->getFile() . " - " . $exception->getLine() . ": " . $exception->getMessage() . "\n". $exception->getTraceAsString();
                    $this->_helper->error($errorMessage);
                    $shippingArray = array();
                }

                $product['shipping'] = $shippingArray;

                $product['gtin'] = $this->getMappedConfig('gtin', $item);

                $product['google_category'] = $this->getMappedConfig('google_category', $item);
                $product['adult'] = $this->getMappedConfig('adult', $item);
                $product['brand'] = $this->getMappedConfig('brand', $item);
                $product['mpn'] = $this->getMappedConfig('mpn', $item);
                $product['unit_pricing_measure'] = $this->getMappedConfig('unit_pricing_measure', $item);
                $product['unit_pricing_base_measure'] = $this->getMappedConfig('unit_pricing_base_measure', $item);

                // If configurable product
                if(!empty($parentIDs)) {
                    $product['parent_id'] = $parentIDs[0];
                }
                $product['gender'] = $this->getMappedConfig('gender', $item);
                $product['age_group'] = $this->getMappedConfig('age_group', $item);
                $product['color'] = $this->getMappedConfig('color', $item);
                $product['size'] = $this->getMappedConfig('size', $item);
                $product['size_type'] = $this->getMappedConfig('size_type', $item);
                $product['size_system'] = $this->getMappedConfig('size_system', $item);
                $product['material'] = $this->getMappedConfig('material', $item);
                $product['pattern'] = $this->getMappedConfig('pattern', $item);

                $product['free_1'] = $this->getMappedConfig('free_1', $item);
                $product['free_2'] = $this->getMappedConfig('free_1', $item);
                $product['free_3'] = $this->getMappedConfig('free_1', $item);


                $productsArray[] = $product;
            }

            $resultArray = array('offset' => $offset,
                'products' => $productsArray
            );

            $this->_helper->sendResponse($resultArray);
        } catch(\Exception $exception) {
            $errorMessage = $exception->getFile() . " - " . $exception->getLine() . ": " . $exception->getMessage() . "\n". $exception->getTraceAsString();
            $this->_helper->error($errorMessage);
            throw new \Exception($errorMessage);
        }

        $this->_helper->log('Products Endpoint END');
    }

    protected function getMappedConfig($field, $product) {
        $configVal = $this->_helper->getConfig('easymarketingsection/easymarketingassign/' . $field);

        $result = null;
        if(!empty($configVal)) {
            $attributeCodes = explode(',', $configVal);

            $attributeIterator = 0;
            foreach($attributeCodes as $attributeCode) {
                if($attributeIterator == 0) {
                    $attributeIterator++;
                    if(!empty($attributeCode)) {
                        return $attributeCode; // Default value
                    }
                    continue;
                }

                $value = $product->getData($attributeCode);
                if(empty($value)) {
                    $parentIDs = $this->_catalogProductTypeConfigurable->getParentIdsByChild($product->getId());
                    if(!empty($parentIDs[0])) {
                        $parentProduct = $this->_productFactory->create()->load($parentIDs[0]);
                        $value = $parentProduct->getData($attributeCode);
                        if(empty($value)) {
                            continue;
                        }
                    } else {
                        continue;
                    }
                }

                $attribute = $this->_helper->getAttributeByCode($attributeCode);
                $frontendInput = $attribute->getFrontendInput();

                if($frontendInput == 'multiselect' || $frontendInput == 'select') {
                    $multiselectValues = explode(',', $value);
                    $resultValues = array();
                    foreach($multiselectValues as $multiselectValue) {
                        $options = $attribute->getOptions();
                        foreach($options as $option) {
                            if($option->getValue() == $multiselectValue) {
                                $resultValues[] = $option->getLabel();
                                break;
                            }
                        }
                    }

                    $result = implode(", ", $resultValues);
                } else {
                    $result = $value;
                }
                break;
            }
        }

        return $result;
    }
}

