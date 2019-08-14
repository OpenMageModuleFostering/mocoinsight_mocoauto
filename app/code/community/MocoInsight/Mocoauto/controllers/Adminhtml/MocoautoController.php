<?php

require_once(Mage::getModuleDir('', 'MocoInsight_Mocoauto') . DS . 'Helper' . DS . 'JWT.php');

class MocoInsight_Mocoauto_Adminhtml_MocoautoController extends Mage_Adminhtml_Controller_Action
{
    protected $_publicActions = array('redirect', 'authenticate');

    public function redirectAction()
    {
        $type = $this->getRequest()->getParam('type');
        $id = $this->getRequest()->getParam('id');

        if($id && $type && in_array($type, array('settings'))) {
            switch($type) {
                case 'settings':
                    $this->_redirect('adminhtml/system_config/edit/section/mocoauto');
                    break;

            }
        } else {
            $this->_redirect(Mage::getSingleton('admin/session')->getUser()->getStartupPageUrl());
        }
    }

    public function generateAction()
    {
        try {
            Mage::helper('mocoauto')->setApiToken();
            Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('mocoauto')->__('Successfully generated new API token'));
        } catch(Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getCode() . ': ' . $e->getMessage());
        }

        $this->_redirect('adminhtml/system_config/edit/section/mocoauto');
    }
}
