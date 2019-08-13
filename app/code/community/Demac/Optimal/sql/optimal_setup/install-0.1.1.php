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

$installer->endSetup();