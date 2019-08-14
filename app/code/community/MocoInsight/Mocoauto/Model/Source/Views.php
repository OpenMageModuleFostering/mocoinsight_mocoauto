<?php

class MocoInsight_Mocoauto_Model_Source_Views
{
    protected $_options;

    public function toOptionArray($isMultiselect=false)
    {
        if (!$this->_options) {
            try {
                $views = Mage::getModel('mocoauto/api_views')->active();
                foreach($views as $view) {
                    $this->_options[] = array(
                        'value' => $view['id'],
                        'label' => $view['title'],
                    );
                }
            } catch(Exception $e) {
                // Just don't display anything
            }

        }

        $options = $this->_options;

        if(!$isMultiselect){
            array_unshift($options, array('value'=>'', 'label'=> Mage::helper('adminhtml')->__('--Please Select--')));
        }

        return $options;
    }
}
