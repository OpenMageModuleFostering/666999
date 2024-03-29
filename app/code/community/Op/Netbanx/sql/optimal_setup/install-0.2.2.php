<?php

$installer = $this;
$installer->startSetup();
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
  `nickname` varchar(255) NOT NULL DEFAULT '',
  `card_expiration` varchar(255) NOT NULL DEFAULT '',
  `payment_token` varchar(255) NOT NULL DEFAULT '',
  `last_four_digits` varchar(4) NOT NULL DEFAULT '',
  `profile_id` varchar(255) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`entity_id`),
  KEY `profile_id` (`profile_id`),
  KEY `customer_id` (`customer_id`),
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



$installer->endSetup();