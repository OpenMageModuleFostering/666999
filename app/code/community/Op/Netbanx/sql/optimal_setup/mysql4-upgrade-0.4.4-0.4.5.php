<?php
$installer = $this;

$installer->startSetup();

$installer->getConnection()->addColumn($installer->getTable('demac_optimal_merchant_customer'),
    'generated_merchant_id', 'varchar(100) NOT NULL');

$installer->endSetup();