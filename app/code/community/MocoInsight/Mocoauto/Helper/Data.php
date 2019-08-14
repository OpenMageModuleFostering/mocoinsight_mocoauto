<?php


class MocoInsight_Mocoauto_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function getApiToken($generate = true)
    {
        // Grab any existing token from the admin scope
        $token = Mage::getStoreConfig('mocoauto/api/token', 0);

        if( (!$token || strlen(trim($token)) == 0) && $generate) {
            $token = $this->setApiToken();
        }

        return $token;
    }

    public function setApiToken($token = null)
    {
        if(!$token) {
            $token = md5(time());
        }
        Mage::getModel('core/config')->saveConfig('mocoauto/api/token', $token, 'default');

        return $token;
    }
}
