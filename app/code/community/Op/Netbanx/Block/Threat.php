<?php

class Op_Netbanx_Block_Threat extends Mage_Core_Block_Template
{

    protected function _construct()
    {
        $this->addData(array(
            'cache_lifetime' => null,
            'cache_tags'     => array('OPTIMAL_THREATMETRIX'),
            'cache_key'      => rand(),
        ));
    }

    /**
     * Get the organization id
     *
     * @return mixed
     */
    public function getOrgId()
    {
        $orgId = Mage::getStoreConfig('payment/threat_metrix/org_id');
        return $orgId;
    }

    /**
     * Check if treath Metrix is actige
     *
     * @return mixed
     */
    public function isThreatMetrixActive()
    {
        $enabled = Mage::getStoreConfig('payment/threat_metrix/active');
        return $enabled;
    }

    /**
     * Generate the Unique session key
     *
     * @param $sessionId
     * @return bool|string
     */
    public function getSessionKey($sessionId)
    {
        $orgId = $this->getOrgId();
        $time =  number_format(round(microtime(true) * 1000), 0, '.', '');

        if(isset($sessionId) && isset($orgId) && isset($time))
        {
            $key = "{$orgId}_{$time}_{$sessionId}";
            Mage::getSingleton('core/session')->setThreatMetrixSessionKey($key);

            return $key;
        }

        return false;
    }

    /**
     * Generate the Threat Metrix url
     *
     * @param null $m
     * @return string
     */
    public function getThreatMetrixUrl($m = null)
    {
        $orgId = $this->getOrgId();
        $sessionId = $this->getSessionKey(Mage::getSingleton("core/session")->getEncryptedSessionId());

        $url = "https://h.online-metrix.net/fp/check.js?org_id={$orgId}&session_id={$sessionId}";

        if(isset($m))
        {
            $url .= "&m={$m}";
        }

        return $url;
    }


}