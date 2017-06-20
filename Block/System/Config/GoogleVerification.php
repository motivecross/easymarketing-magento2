<?php
namespace Motive\Easymarketing\Block\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Motive\Easymarketing\Helper\Data;

class GoogleVerification extends Field
{

    protected $_helper;

    protected $_storeManager;

    protected $_getUrl = 'https://api.easymarketing.de/site_verification_data';

    protected $_performUrl = 'https://api.easymarketing.de/perform_site_verification';

    /**
     * @var string
     */
    protected $_template = 'Motive_Easymarketing::system/config/google_verification.phtml';

    /**
     * @param Context $context
     * @param Data $helper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data $helper,
        array $data = []
    ) {
        $this->_helper = $helper;
        $this->_storeManager = $context->getStoreManager();
        parent::__construct($context, $data);
    }

    /**
     * Remove scope label
     *
     * @param  AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Return element html
     *
     * @param  AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    /**
     * Return ajax url for endpoint button
     *
     * @return string
     */
    public function getVerificationUrl()
    {
        $accessToken = $this->_helper->getConfig('easymarketingsection/easmarketinggeneral/access_token');

        return $this->_getUrl . '?access_token=' . $accessToken;
    }

    /**
     * Return ajax params for configuration endpoint
     *
     * @return string
     */
    public function getAjaxParams() {
        $paramsArray = array();

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

        return json_encode($paramsArray, $this->_helper->jsonParameters);
    }

    /**
     * Return url for extraction status
     *
     * @return string
     */
    public function getExtractionUrl() {
        $accessToken = $this->_helper->getConfig('easymarketingsection/easmarketinggeneral/access_token');

        $storeId = $this->_helper->getParam('store');
        $baseUrl = $this->_storeManager->getStore($storeId)->getBaseUrl();

        return $this->_extractionUrl . '?access_token=' . $accessToken . '&website_url=' . parse_url($baseUrl, PHP_URL_HOST);
    }

    /**
     * Generate endpoint button html
     *
     * @return string
     */
    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock(
            'Magento\Backend\Block\Widget\Button'
        )->setData(
            [
                'id' => 'googleverification_button',
                'label' => __('Google Site Verification durchführen'),
            ]
        );

        return $button->toHtml();
    }
}