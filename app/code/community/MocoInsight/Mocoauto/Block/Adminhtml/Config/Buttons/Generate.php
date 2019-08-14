<?php

class MocoInsight_Mocoauto_Block_Adminhtml_Config_Buttons_Generate extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate('mocoauto/config/button-generate.phtml');
        }
        return $this;
    }

    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $originalData = $element->getOriginalData();
        $this->addData(array(
            'button_label' => Mage::helper('mocoauto')->__($originalData['button_label']),
            'html_id' => $element->getHtmlId(),
            'url' => Mage::getSingleton('adminhtml/url')->getUrl('*/setup/start')
        ));

        return $this->_toHtml();
    }
}
