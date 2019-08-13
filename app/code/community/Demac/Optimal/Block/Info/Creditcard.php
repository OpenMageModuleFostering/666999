<?php
class Demac_Optimal_Block_Info_Creditcard extends Mage_Payment_Block_Info_Cc
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('optimal/info/creditcard.phtml');
    }

    /**
     * Get the used card information
     *
     * @return Mage_Payment_Model_Info|void
     */
    public function getCardInfo()
    {
        $info = $this->getInfo();
        $profileId = false;
        if($info->getOrder() && $info->getOrder()->getPayment()) {
            $orderInfo = unserialize($info->getOrder()->getPayment()->getAdditionalInformation('order'));
            if(isset($orderInfo['optimal_profile_id']) && $orderInfo['optimal_profile_id'] > 0)
            {
                $profile = Mage::getModel('optimal/creditcard')
                    ->load($orderInfo['optimal_profile_id']);
            }
        }

        if(isset($profile)) {

            $info = array(
                'name' => $profile->getCardHolder(),
                'card_type' => $profile->getCardNickname(),
                'card_number' => $profile->getLastFourDigits(),
                'card_exp' => $profile->getCardExpiration(),
            );

            return $info;
        }

        $info = array(
            'card_type' => $this->getCcTypeName(),
            'card_number' => $info->getCcLast4(),
            'card_exp' => $info->getCcExpMonth() . '/' . $info->getCcExpYear(),
        );

        return $info;
    }
}