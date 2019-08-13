<?php

$installer = $this;
$installer->startSetup();

$table = $installer->getConnection()
    ->newTable($installer->getTable('optimal/merchant_customer'))
    ->addColumn('merchant_customer_id', Varien_Db_Ddl_Table::TYPE_INTEGER, NULL, array(
        'identity' => TRUE,
        'unsigned' => TRUE,
        'nullable' => FALSE,
        'primary'  => TRUE,
    ), 'Merchant Customer ID')
    ->addColumn('customer_id', Varien_Db_Ddl_Table::TYPE_INTEGER, NULL, array(
        'unsigned' => TRUE,
        'nullable' => FALSE,
        'primary'  => TRUE
    ), 'Customer ID');

$installer->getConnection()->createTable($table);

$installer->endSetup();