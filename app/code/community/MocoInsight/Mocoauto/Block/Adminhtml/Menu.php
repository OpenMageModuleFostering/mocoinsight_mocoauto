<?php

class MocoInsight_Mocoauto_Block_Adminhtml_Menu extends Mage_Adminhtml_Block_Template
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('page_tabs');
        $this->setTemplate('mocoauto/left-menu.phtml');
    }
}
