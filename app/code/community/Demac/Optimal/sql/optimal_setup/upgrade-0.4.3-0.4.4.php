<?php
$installer = $this;

$installer->startSetup();

$installer->getConnection()->addColumn($installer->getTable('sales_flat_quote_payment'),
    'optimal_use_interac', 'TINYINT(1) NOT NULL DEFAULT "0" AFTER `optimal_create_profile`');

$installer->getConnection()->addColumn($installer->getTable('sales_flat_order_payment'),
    'optimal_use_interac', 'TINYINT(1) NOT NULL DEFAULT "0" AFTER `optimal_create_profile`');

$installer->endSetup();