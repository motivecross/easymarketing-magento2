<?php

namespace Motive\Easymarketing\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $table = $setup->getConnection()->newTable(
            $setup->getTable('easymarketing_data')
        )->addColumn(
            'data_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true]
        )->addColumn(
            'data_name',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            []
        )->addColumn(
            'data_value',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            '2M',
            []
        )->addColumn(
            'data_scope',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['nullable' => false]
        )->addColumn(
            'data_modified',
            \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE],
            'Updated At'
        )->setComment(
            'Easymarketing Table'
        );
        $setup->getConnection()->createTable($table);

        $setup->endSetup();
    }
}