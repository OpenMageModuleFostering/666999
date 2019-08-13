<?php
$installer = $this;

$installer->startSetup();

$installer->run("

DROP TABLE IF EXISTS `{$this->getTable('optimal/errorcode')}`;
CREATE TABLE IF NOT EXISTS `{$this->getTable('optimal/errorcode')}` (
  `code` varchar(50) NOT NULL,
  `message` varchar(555) NOT NULL,
  `custom_message` varchar(555) NULL,
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

");

$installer->endSetup();