<?php
$installer = $this;

$installer->startSetup();

$installer->run("

DROP TABLE IF EXISTS `{$this->getTable('optimal/errorcode')}`;
CREATE TABLE IF NOT EXISTS `{$this->getTable('optimal/errorcode')}` (
  `code` varchar(5) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

");

$installer->run("

DROP TABLE IF EXISTS `{$this->getTable('optimal/risk')}`;
CREATE TABLE IF NOT EXISTS `{$this->getTable('optimal/risk')}` (
  `entity_id` int(20) unsigned NOT NULL AUTO_INCREMENT,
  `risk_code` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`entity_id`),
  KEY `risk_code` (`risk_code`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

");

$installer->run("

DROP TABLE IF EXISTS `{$this->getTable('optimal/creditcard')}`;
CREATE TABLE IF NOT EXISTS `{$this->getTable('optimal/creditcard')}` (
  `entity_id` int(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(20) NOT NULL DEFAULT 0,
  `merchant_customer_id` varchar(255) NOT NULL DEFAULT '',
  `card_id` int(20) NOT NULL DEFAULT 0,
  `card_holder` varchar(255) NOT NULL DEFAULT '',
  `card_nickname` varchar(255) NOT NULL DEFAULT '',
  `card_expiration` varchar(255) NOT NULL DEFAULT '',
  `payment_token` varchar(255) NOT NULL DEFAULT '',
  `last_four_digits` varchar(4) NOT NULL DEFAULT '',
  `profile_id` varchar(255) NOT NULL DEFAULT '',
  `is_deleted` boolean NOT NULL DEFAULT false,
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`entity_id`),
  KEY `profile_id` (`profile_id`),
  KEY `customer_id` (`customer_id`),
  KEY `card_id` (`card_id`),
  KEY `merchant_customer_id` (`merchant_customer_id`),
  KEY `payment_token` (`payment_token`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

");


$installer->getConnection()->addColumn($installer->getTable('sales_flat_quote_payment'),
    'optimal_create_profile', 'BOOLEAN NOT NULL AFTER `method`');

$installer->getConnection()->addColumn($installer->getTable('sales_flat_order_payment'),
    'optimal_create_profile', 'BOOLEAN NOT NULL AFTER `method`');

$installer->getConnection()->addColumn($installer->getTable('sales_flat_quote_payment'),
    'optimal_profile_id', 'VARCHAR(255) NOT NULL DEFAULT "" AFTER `method`');

$installer->getConnection()->addColumn($installer->getTable('sales_flat_order_payment'),
    'optimal_profile_id', 'VARCHAR(255) NOT NULL DEFAULT "" AFTER `method`');

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