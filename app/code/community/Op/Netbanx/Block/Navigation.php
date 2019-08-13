<?php

class Op_Netbanx_Block_Navigation extends Mage_Customer_Block_Account_Navigation
{
    public function getLinks()
    {
        if (!Mage::helper('optimal')->canShow()) {
            if (isset($this->_links['optimal_profiles'])) {
                unset($this->_links['optimal_profiles']);
            }
        }

        return parent::getLinks();
    }

    // KL: Backward compatiblity issue fix
    public function removeLinkByName($name) {
        unset($this->_links[$name]);
    }

}