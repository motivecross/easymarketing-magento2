<?php

namespace Motive\Easymarketing\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class InstallData implements InstallDataInterface
{
    protected $_resourceConfig;

    public function __construct(
        \Magento\Framework\App\Config\ConfigResource\ConfigInterface $resourceConfig)
    {
        $this->_resourceConfig = $resourceConfig;
    }

    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context) {

        $setup->getConnection()->query("INSERT INTO easymarketing_data SET data_name = 'configuration_status', data_value = 0");
        $setup->getConnection()->query("INSERT INTO easymarketing_data SET data_name = 'configuration_last_errors', data_value = ''");

        $shopToken = sha1(mt_rand(10, 1000) . time());

        $this->_resourceConfig->saveConfig(
            'easymarketingsection/easmarketinggeneral/shop_token',
            $shopToken,
            \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            \Magento\Store\Model\Store::DEFAULT_STORE_ID
        );
    }
}