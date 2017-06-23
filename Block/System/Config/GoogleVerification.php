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
    public function render(AbstractElement $element) {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Return element html
     *
     * @param  AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element) {
        return $this->_toHtml();
    }

    /**
     * Get Verification Ajax Url
     *
     * @return string
     */
    public function getVerificationUrl() {
        $storeId = $this->_helper->getParam('store');
        $baseUrl = $this->_storeManager->getStore($storeId)->getBaseUrl();

        return $baseUrl . "easymarketing/system/savegoogleverification";
    }

    /**
     * Generate button html
     *
     * @return string
     */
    public function getButtonHtml() {
        $button = $this->getLayout()->createBlock(
            'Magento\Backend\Block\Widget\Button'
        )->setData(
            [
                'id' => 'verification_button',
                'label' => __('Google Site Verification durchführen / aufheben'),
            ]
        );

        return $button->toHtml();
    }

    public function getCurrentStatus() {
        $status = $this->_helper->dbFetchOne("google_verification_status");
        $meta = $this->_helper->dbFetchOne("google_verification_meta");

        if(empty($status) || empty($meta)) {
            return 0;
        } else {
            return 1;
        }
    }

    /**
     * Generate current status image
     *
     * @return string
     */
    public function getCurrentStatusImage() {
        if($this->getCurrentStatus()) {
            return $this->getSuccessImage();
        } else {
            return $this->getFailImage();
        }
    }

    /**
     * Generate success image
     *
     * @return string
     */
    public function getSuccessImage() {
        return $this->getViewFileUrl('Motive_Easymarketing::images/rule_component_apply.gif');
    }

    /**
     * Generate fail image
     *
     * @return string
     */
    public function getFailImage() {
        return $this->getViewFileUrl('Motive_Easymarketing::images/rule_component_remove.gif');
    }
}