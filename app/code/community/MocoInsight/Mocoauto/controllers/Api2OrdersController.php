<?php
//
//  Make sure you update version in /var/www/html/app/code/community/MocoInsight/Mocoauto/etc/config.xml
//
//  DEBUG example  "Mage::log('DBG Count: '.$customercount);"
//
//  Provides the following actions via a RestAPI
//
//  ordersAction


define("apiversion","2.0.0");

class MocoInsight_Mocoauto_Api2OrdersController extends Mage_Core_Controller_Front_Action
{



    public function _authorise()
    {
        $tokenString = $this->getRequest()->getHeader('mocoapi');

        $token = null;
        $matches = array();
        if(preg_match('/apikey="([a-z0-9]+)"/', $tokenString, $matches)) {
            $token = $matches[1];
        }

        $apiToken = Mage::helper('mocoauto')->getApiToken(false);

    // Check API enabled

        if(!Mage::getStoreConfig('mocoauto/api/enabled')) {
                $this->getResponse()
                    ->setBody(json_encode(array('success' => false, 'message' => 'API access disabled', 'MocoAPI version' =>apiversion)))
                    ->setHttpResponseCode(403)
                    ->setHeader('Content-type', 'application/json', true);
                return false;
        }

        // Check the token passed in the header
        if(!$token || $token != $apiToken) {
                $this->getResponse()
                    ->setBody(json_encode(array('success' => false, 'message' => 'Not authorised','MocoAPI version' => apiversion)))
                    ->setHttpResponseCode(401)
                    ->setHeader('Content-type', 'application/json', true);
                return false;
        }


        // Check the URL doesnt have anything apended to it
        if(substr_count($this->getRequest()->getPathInfo(), '/') !=3) {
                $this->getResponse()
                    ->setBody(json_encode(array('success' => false, 'message' => 'Malformed url')))
                    ->setHttpResponseCode(401)
                    ->setHeader('Content-type', 'application/json', true);
                return false;
        }


        return true;
    }

    public function ordersAction()
    {


        if(!$this->_authorise()) {
            return $this;
        }

        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));

        $offset = $this->getRequest()->getParam('offset', 0);
        $page_size = $this->getRequest()->getParam('page_size', 20);
        $since = $this->getRequest()->getParam('since','ALL');
        $gTE = $this->getRequest()->getParam('gte', 'ALL');

        $_orderCol = Mage::getModel('sales/order')->getCollection()->addAttributeToSelect('*');


        if($since != 'ALL'){    
            $_orderCol->addAttributeToFilter('updated_at', array('gteq' =>$since));
        }

        if($gTE != 'ALL'){
            $_orderCol->addFieldToFilter('entity_id', array('gteq' =>$gTE));
            $_orderCol->getSelect()->limit($page_size, ($offset * $page_size))->order('entity_id');
        }
        else{
            $_orderCol->getSelect()->limit($page_size, ($offset * $page_size))->order('updated_at');
        }

        //Mage::log('SQL Query: '.$_orderCol->getSelect());


        $orders = array();

        foreach($_orderCol as $_order) {
 
            $order = array();

            try{
                $order['moco_start_of_order_record'] = 'True';
                $orderdetails = array();
                $orderdetails = $_order->toArray();
                foreach ($orderdetails as $key => $value) {
                    $order[$key] = $value;
                }
                if(is_object($_order->getPayment())){
                    $order['payment_method'] = $_order->getPayment()->getMethodInstance()->getTitle();
                }
                else{
                    $order['payment_method'] = 'Unable to get payment_method';
                }

// Removing Tax Class as the customer really wanted VAT number 
//                $_quote = Mage::getModel('sales/quote')->load($_order->getQuoteId());
//                $taxClassId = $_quote->getCustomerTaxClassId();
//                $_taxClass = Mage::getModel('tax/class')->load($taxClassId);
//                $order['moco_customer_tax_class'] = $_taxClass->getClassName();

                if(is_object($_order->getBillingAddress())){
                    $_billing_address = $_order->getBillingAddress();
                    $billaddrdetails = array();
                    $billaddrdetails[] = $_billing_address->toArray();
                    $order['moco_address'] = $billaddrdetails;
                }

                if(is_object($_order->getShippingAddress())){

                    $_shipping_address = $_order->getShippingAddress();
                    $shipaddrdetails = array();
                    $shipaddrdetails[] = $_shipping_address->toArray();
                    $order['moco_ship_address'] = $shipaddrdetails;
                }

                $_orderItemsCol = $_order->getItemsCollection();
                $orderitems = array();
                foreach($_orderItemsCol as $_orderitem){
                    $orderitems[] = $_orderitem->toArray();
                }
                $order['moco_tls'] = $orderitems;


                $order['moco_end_of_order_record'] = 'True';
            }
            catch (Exception $e) {
                $order['mocoauto_api_error'] = 'order record: ' . $e->getMessage();
            }
            $orders[] = $order;

        }

        $this->getResponse()
            ->setBody(json_encode($orders))
            ->setHttpResponseCode(200)
            ->setHeader('Content-type', 'application/json', true);
        return $this;
    }
}
