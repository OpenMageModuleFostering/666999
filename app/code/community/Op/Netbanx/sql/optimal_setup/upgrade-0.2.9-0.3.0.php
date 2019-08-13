<?php

$installer = $this;
$installer->startSetup();
$installer->run("
ALTER TABLE `{$this->getTable('optimal/creditcard')}`
MODIFY `profile_id` VARCHAR(80)

");


$installer->endSetup();