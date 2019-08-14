<?php

class MocoInsight_Mocoauto_Model_Observer
{
    public function setHook(Varien_Event_Observer $observer)
    {
        if (Mage::app()->getFrontController()->getAction()->getFullActionName() === 'adminhtml_dashboard_index')
        {
            $block = $observer->getBlock();
            if ($block->getNameInLayout() === 'dashboard')
            {
                $block->getChild('totals')->setUseAsDashboardHook(true);
            }
        }
    }



    public function insertBlock(Varien_Event_Observer $observer)
    {
        //Mage::log(sprintf("%s->EventName=%s", __METHOD__, $observer->getName()) );
    }    
    
    public function saveConfig(Varien_Event_Observer $observer) {
        //Mage::log(sprintf("%s->EventName=%s", __METHOD__, $observer->getName()) );
    }
}
