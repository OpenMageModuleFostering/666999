<?php

$installer = $this;

$installer->startSetup();

$addCol = false;

try {
    $installer->run("ALTER TABLE `{$installer->getTable('sales_flat_quote_payment')}` MODIFY `optimal_profile_id` VARCHAR(255) NOT NULL DEFAULT \"\" AFTER `method`;");
} catch (Exception $e) {
    $msg = strtolower($e->getMessage());
    if (strpos($msg, 'column not found') !== false) {
        $addCol = true;
    }
}

if ($addCol) {
    $installer->getConnection()->addColumn($installer->getTable('sales_flat_quote_payment'),
        'optimal_profile_id', 'VARCHAR(255) NOT NULL DEFAULT "" AFTER `method`');
}

$addCol = false;

try {
    $installer->run("ALTER TABLE `{$installer->getTable('sales_flat_order_payment')}` MODIFY `optimal_profile_id` VARCHAR(255) NOT NULL DEFAULT \"\" AFTER `method`;");
} catch (Exception $e) {
    $msg = strtolower($e->getMessage());
    if (strpos($msg, 'column not found') !== false) {
        $addCol = true;
    }
}

if ($addCol) {
    $installer->getConnection()->addColumn($installer->getTable('sales_flat_order_payment'),
        'optimal_profile_id', 'VARCHAR(255) NOT NULL DEFAULT "" AFTER `method`');
}

$addCol = false;

try {
    $installer->getConnection()->addColumn($installer->getTable('sales_flat_quote_payment'),
        'optimal_create_profile', 'BOOLEAN NOT NULL AFTER `method`');
} catch (Exception $e) {
    // means col exists already
}

try {
    $installer->getConnection()->addColumn($installer->getTable('sales_flat_order_payment'),
        'optimal_create_profile', 'BOOLEAN NOT NULL AFTER `method`');
} catch (Exception $e) {
    // means col exists already
}

try {
    $installer->getConnection()
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
} catch (Exception $e) {
    // means table exists already
}

$installer->endSetup();