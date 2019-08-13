<?php

$installer = $this;

$installer->startSetup();

$installer->run("
ALTER TABLE `{$installer->getTable('sales_flat_quote_payment')}` MODIFY `optimal_profile_id` VARCHAR(255) NOT NULL DEFAULT \"\" AFTER `method`;
ALTER TABLE `{$installer->getTable('sales_flat_order_payment')}` MODIFY `optimal_profile_id` VARCHAR(255) NOT NULL DEFAULT \"\" AFTER `method`;
");

$installer->endSetup();