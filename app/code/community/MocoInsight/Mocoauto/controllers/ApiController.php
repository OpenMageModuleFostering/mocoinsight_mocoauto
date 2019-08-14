<?php
//
//  Make sure you update version in /var/www/html/app/code/community/MocoInsight/Mocoauto/etc/config.xml
//
//  DEBUG example  "Mage::log('DBG Count: '.$customercount);"
//
//  Provides the following actions via a RestAPI
//
//  statsAction     
//  ordersAction
//  ordersNoPaymentAction for trollweb Norwegan clients.
//  customersAction
//  categoriesAction
//  productsAction
//  stocklevelsAction
//  log_urlAction
//  log_url_infoAction
//  log_url_joinedAction
//  log_visitorAction
//  log_visitor_infoAction
//  log_visitor_joinedAction
//  log_customerAction
//  subscribersAction
//  storesAction
//  unconvertedcartsAction
//  wishlistsAction
//  installinfoAction
//  rulesAction
//  eavinfo_catalogAction
//  attrinfoAction
//  entitytypeinfoAction
//  order_idsAction
//  customer_idsAction
//  product_idsAction


define("apiversion","1.5.0.4");

class MocoInsight_Mocoauto_ApiController extends Mage_Core_Controller_Front_Action
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


    public function statsAction()   
    {
        if(!$this->_authorise()) {
            return $this;
        }

        $time_start = microtime(true); 

        $currentSystemTime = date('Y-m-d H:i:s', time());
        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));
        $since = $this->getRequest()->getParam('since','ALL');

        Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID); //	set to admin view all sites and stores

        $_productCol = Mage::getModel('catalog/product')->getCollection();
        if($since != 'ALL'){    
           $_productCol->addAttributeToFilter('updated_at', array('gteq' =>$since));
        }
        $productcount = $_productCol->getSize();
            
        $_orderCol = Mage::getModel('sales/order')->getCollection();
        if($since != 'ALL'){    
           $_orderCol->addAttributeToFilter('updated_at', array('gteq' =>$since));
        }
        $ordercount = $_orderCol->getSize();
 
        $_customerCol = Mage::getModel('customer/customer')->getCollection();
        if($since != 'ALL'){    
           $_customerCol->addAttributeToFilter('updated_at', array('gteq' =>$since));
        }
        $customercount = $_customerCol->getSize();


        $_categoryCol = Mage::getModel('catalog/category')->getCollection();
        if($since != 'ALL'){    
           $_categoryCol->addAttributeToFilter('updated_at', array('gteq' =>$since));
        }
        $categorycount = $_categoryCol->getSize();

        $_wishlistCol = Mage::getModel('wishlist/wishlist')-> getCollection();
        if($since != 'ALL'){
           $_wishlistCol->addFieldToFilter('updated_at', array('gteq' =>$since));
        }
        $wishlistcount = $_wishlistCol->getSize();

        $_cartsCol = Mage::getResourceModel('sales/quote_collection')->addFieldToFilter('is_active', '1');
        if($since != 'ALL'){
            $_cartsCol->addFieldToFilter('updated_at', array('gteq' =>$since));
	}
        else{
           $_cartsCol->addFieldToFilter('items_count', array('neq' => 0));
        } 
        
        $cartscount = $_cartsCol->getSize();

        $_subscriberCol = Mage::getModel('newsletter/subscriber')-> getCollection();

        $subscribercount = $_subscriberCol->getSize();

        $_rulesCol = Mage::getModel('salesrule/rule')->getCollection();

        $rulescount = $_rulesCol->getSize();


    $magentoVersion = Mage::getVersion();
    $moduleversion = (String)Mage::getConfig()->getNode()->modules->MocoInsight_Mocoauto->version;
    $phpversion = phpversion();

    $stats = array(
        'success' => 'true',
        'Since' => $since,
        'Products' => $productcount,
        'Orders' => $ordercount,
        'Customers' => $customercount,
        'Categories' => $categorycount,
        'Wish lists' => $wishlistcount,
        'Unconverted carts' => $cartscount,
        'Subscribers' => $subscribercount,
        'Cart and Coupon rules' => $rulescount,
        'System Date Time' => $currentSystemTime,
        'Magento Version' => $magentoVersion,
        'MocoAPI Version' => apiversion,
        'Module Version' => $moduleversion,
        'PHP Version' => $phpversion,
        'API processing time' => (microtime(true) - $time_start)
         );
     
        $this->getResponse()
            ->setBody(json_encode($stats))
            ->setHttpResponseCode(200)
            ->setHeader('Content-type', 'application/json', true);
        return $this;
    }

    public function test_statsAction()   // test adding edition info
    {
        if(!$this->_authorise()) {
            return $this;
        }

        $time_start = microtime(true); 

        $currentSystemTime = date('Y-m-d H:i:s', time());
        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));
        $since = $this->getRequest()->getParam('since','ALL');

        Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID); //	set to admin view all sites and stores

        $_productCol = Mage::getModel('catalog/product')->getCollection();
        if($since != 'ALL'){    
           $_productCol->addAttributeToFilter('updated_at', array('gteq' =>$since));
        }
        $productcount = $_productCol->getSize();
            
        $_orderCol = Mage::getModel('sales/order')->getCollection();
        if($since != 'ALL'){    
           $_orderCol->addAttributeToFilter('updated_at', array('gteq' =>$since));
        }
        $ordercount = $_orderCol->getSize();
 
        $_customerCol = Mage::getModel('customer/customer')->getCollection();
        if($since != 'ALL'){    
           $_customerCol->addAttributeToFilter('updated_at', array('gteq' =>$since));
        }
        $customercount = $_customerCol->getSize();


        $_categoryCol = Mage::getModel('catalog/category')->getCollection();
        if($since != 'ALL'){    
           $_categoryCol->addAttributeToFilter('updated_at', array('gteq' =>$since));
        }
        $categorycount = $_categoryCol->getSize();

        $_wishlistCol = Mage::getModel('wishlist/wishlist')-> getCollection();
        if($since != 'ALL'){
           $_wishlistCol->addFieldToFilter('updated_at', array('gteq' =>$since));
        }
        $wishlistcount = $_wishlistCol->getSize();

        $_cartsCol = Mage::getResourceModel('sales/quote_collection')->addFieldToFilter('is_active', '1');
        if($since != 'ALL'){
            $_cartsCol->addFieldToFilter('updated_at', array('gteq' =>$since));
	}
        else{
           $_cartsCol->addFieldToFilter('items_count', array('neq' => 0));
        } 
        
        $cartscount = $_cartsCol->getSize();

        $_subscriberCol = Mage::getModel('newsletter/subscriber')-> getCollection();

        $subscribercount = $_subscriberCol->getSize();

        $_rulesCol = Mage::getModel('salesrule/rule')->getCollection();

        $rulescount = $_rulesCol->getSize();


    $magentoVersion = Mage::getVersion();
    $moduleversion = (String)Mage::getConfig()->getNode()->modules->MocoInsight_Mocoauto->version;
    $phpversion = phpversion();
    $magentoedition = (String)Mage::getEdition();

    $stats = array(
        'success' => 'true',
        'Since' => $since,
        'Products' => $productcount,
        'Orders' => $ordercount,
        'Customers' => $customercount,
        'Categories' => $categorycount,
        'Wish lists' => $wishlistcount,
        'Unconverted carts' => $cartscount,
        'Subscribers' => $subscribercount,
        'Cart and Coupon rules' => $rulescount,
        'System Date Time' => $currentSystemTime,
        'Magento Version' => $magentoVersion,
        'Magento Edition' => $magentoedition,
        'MocoAPI Version' => apiversion,
        'Module Version' => $moduleversion,
        'PHP Version' => $phpversion,
        'API processing time' => (microtime(true) - $time_start)
         );
     
        $this->getResponse()
            ->setBody(json_encode($stats))
            ->setHttpResponseCode(200)
            ->setHeader('Content-type', 'application/json', true);
        return $this;
    }

    public function logstatsAction()   // Return the number size of logs
    {
        if(!$this->_authorise()) {
            return $this;
        }

        $time_start = microtime(true); 

        $currentSystemTime = date('Y-m-d H:i:s', time());
        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));
        $since = $this->getRequest()->getParam('since','ALL');


        $_read = Mage::getSingleton('core/resource')->getConnection('core_read');

        if (method_exists($_read, 'showTableStatus')){

            $tablename = 'log_url';
            if(!$_read ->showTableStatus(trim($tablename,"'"))){
            $logurlcount = "table does not exist";
            }
            else{
                $query = 'select count(*) AS id from ' . $tablename;
                $log_urlcount = $_read->fetchOne($query);
            }

            $tablename = 'log_url_info';
            if(!$_read ->showTableStatus(trim($tablename,"'"))){
                $log_url_infocount = "table does not exist";
            }
            else{
                $query = 'select count(*) AS id from ' . $tablename;
                $log_url_infocount = $_read->fetchOne($query);
            }

            $tablename = 'log_visitor';
            if(!$_read ->showTableStatus(trim($tablename,"'"))){
                $log_visitorcount = "table does not exist";
            }
            else{
                $query = 'select count(*) AS id from ' . $tablename;
                $log_visitorcount = $_read->fetchOne($query);
            }

            $tablename = 'log_visitor_info';
            if(!$_read ->showTableStatus(trim($tablename,"'"))){
                $log_visitor_infocount = "table does not exist";
            }
            else{
                $query = 'select count(*) AS id from ' . $tablename;
                $log_visitor_infocount = $_read->fetchOne($query);
            }

            $tablename = 'log_customer';        
            if(!$_read ->showTableStatus(trim($tablename,"'"))){
                $log_countcount = "table does not exist";
            }   
            else{
                $query = 'select count(*) AS id from ' . $tablename;
                $log_customercount = $_read->fetchOne($query);
            }
        }
        else {
            $log_urlcount = "showTableStatus is an undefined method";
            $log_url_infocount = "showTableStatus is an undefined method";
            $log_visitorcount = "showTableStatus is an undefined method";
            $log_visitor_infocount = "showTableStatus is an undefined method";
            $log_customercount = "showTableStatus is an undefined method";

        }


    $apiversion = (String)Mage::getConfig()->getNode()->modules->MocoInsight_Mocoauto->version;


    $stats = array(
        'success' => 'true',
        'Since' => $since,
        'log_url' => $log_urlcount,
        'log_url_info' => $log_url_infocount,
        'log_visitor' => $log_visitorcount,
        'log_visitor_info' => $log_visitor_infocount,
        'log_customer' => $log_customercount,
        'MocoAPI Version' => $apiversion,
        'API processing time' => (microtime(true) - $time_start)
         );
     
        $this->getResponse()
            ->setBody(json_encode($stats))
            ->setHttpResponseCode(200)
            ->setHeader('Content-type', 'application/json', true);
        return $this;
    }

    public function order_idsAction()
    {
        if(!$this->_authorise()) {
            return $this;
        }

        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));

        $offset = $this->getRequest()->getParam('offset', 0);
        $page_size = $this->getRequest()->getParam('page_size', 20);

        $orderIds = Mage::getModel('sales/order')->getCollection()->getAllIds($limit= $page_size, $offset =($offset * $page_size));

        $this->getResponse()
            ->setBody(json_encode($orderIds))
            ->setHttpResponseCode(200)
            ->setHeader('Content-type', 'application/json', true);
        return $this;
    }

    public function ordersNoPaymentAction() // Made for Trollweb customer testing.
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

    public function exordersAction()
    {
        if(!$this->_authorise()) {
            return $this;
        }

        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));

        $offset = $this->getRequest()->getParam('offset', 0);
        $page_size = $this->getRequest()->getParam('page_size', 20);
        $since = $this->getRequest()->getParam('since','ALL');

        $_orderCol = Mage::getModel('sales/order')->getCollection()->addAttributeToSelect('*');
        $_orderCol->getSelect()->limit($page_size, ($offset * $page_size))->order('updated_at');

        if($since != 'ALL'){
            $_orderCol->addAttributeToFilter('updated_at', array('gteq' =>$since));
        }

        $orders = array();

        foreach($_orderCol as $_order) {
            $orders[] = $_order->toArray();


            if(is_object($_order->getBillingAddress())){

                $_billing_address = $_order->getBillingAddress();
                $orders[] = $_billing_address->toArray();
            }

            $_orderItemsCol = $_order->getItemsCollection();

            foreach($_orderItemsCol as $_orderitem){
                $orders[] = $_orderitem->toArray();
            }
        }

        $this->getResponse()
            ->setBody(json_encode($orders))
            ->setHttpResponseCode(200)
            ->setHeader('Content-type', 'application/json', true);
        return $this;
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
                if(is_object($_order->getPayment()) && method_exists($_order->getPayment()->getMethodInstance(), 'getTitle')){
                    $order['payment_method'] = $_order->getPayment()->getMethodInstance()->getTitle();
                }
                else{
                    $order['payment_method'] = 'Unable to get payment_method';
                }

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


    public function eavinfo_catalogAction()
    {
        if(!$this->_authorise()) {
            return $this;
        }

        $eavinfo = array();

        $attributeCollection = Mage::getResourceModel('catalog/product_attribute_collection')->getItems();
        foreach($attributeCollection as $attributeObject){
            $eavinfo[] = $attributeObject->getData();
        }

        $this->getResponse()
            ->setBody(json_encode($eavinfo))
            ->setHttpResponseCode(200)
            ->setHeader('Content-type', 'application/json', true);
        return $this;
    }
  
    public function attrinfoAction()
    {
        if(!$this->_authorise()) {
            return $this;
        }

        $eavinfo = array();

        $attributeCollection = Mage::getResourceModel('eav/entity_attribute_collection'); //->setEntityTypeFilter(4);
        foreach($attributeCollection as $attributeObject){
            $eavinfo[] = $attributeObject->getData();
        }

        $this->getResponse()
            ->setBody(json_encode($eavinfo))
            ->setHttpResponseCode(200)
            ->setHeader('Content-type', 'application/json', true);
        return $this;
    }


    public function entitytypeinfoAction()
    {
        $tablename = 'eav_entity_type';     // Set the table name here

        if(!$this->_authorise()) {
            return $this;
        }

        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));

        $offset = $this->getRequest()->getParam('offset', 0);
        $page_size = $this->getRequest()->getParam('page_size', 200);
        $since = $this->getRequest()->getParam('since', 'ALL');

        $_read = Mage::getSingleton('core/resource')->getConnection('core_read');

        if(!$_read ->isTableExists($tablename)){    //Table does not exist
            $readresults=array($tablename ." table does not exist");
        }
        else{
            $query = 'select * from ' . $tablename . ' limit ' . $offset . ',' . $page_size;
            $readresults = $_read->fetchAll($query);
        }

        $this->getResponse()
            ->setBody(json_encode($readresults))
            ->setHttpResponseCode(200)
            ->setHeader('Content-type', 'application/json', true);
        return $this;
    }

    public function customer_idsAction()
    {
        if(!$this->_authorise()) {
            return $this;
        }

        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));

        $offset = $this->getRequest()->getParam('offset', 0);
        $page_size = $this->getRequest()->getParam('page_size', 20);

        Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
        $customerIds = Mage::getModel('customer/customer')->getCollection()->getAllIds($limit= $page_size, $offset =($offset * $page_size));

        $this->getResponse()
            ->setBody(json_encode($customerIds))
            ->setHttpResponseCode(200)
            ->setHeader('Content-type', 'application/json', true);
        return $this;
    }

    public function customersAction()
    {
        if(!$this->_authorise()) {
            return $this;
        }

        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));

        $offset = $this->getRequest()->getParam('offset', 0);
        $page_size = $this->getRequest()->getParam('page_size', 20);
        $since = $this->getRequest()->getParam('since', 'ALL');
        $gTE = $this->getRequest()->getParam('gte', 'ALL');

        $_customerCol = Mage::getModel('customer/customer')->getCollection()->addAttributeToSelect('*');

        if($since != 'ALL'){
            $_customerCol->addAttributeToFilter('updated_at', array('gteq' =>$since));
        }

        if($gTE != 'ALL'){
           $_customerCol->addFieldToFilter('entity_id', array('gteq' =>$gTE));
           $_customerCol->getSelect()->limit($page_size, ($offset * $page_size))->order('entity_id');
        }
        else{
           $_customerCol->getSelect()->limit($page_size, ($offset * $page_size))->order('updated_at');
        }

        $customers = array();

        foreach($_customerCol as $_customer) {

            $attributes = $_customer->getAttributes();
            $customer = array();
            foreach ($attributes as $attribute) {
                $attributeCode = $attribute->getAttributeCode();

                switch ($attributeCode){
                    case 'password_hash':
                        break;
                    case 'rp_token':
                        break;
                    case 'rp_token_created_at':
                        break;
                    case 'store_id':
                         $value = $_customer->getData($attributeCode);
                         $customer[$attributeCode] = $value;
                         break;
                    default:
                        try {
                            $value = $attribute->getFrontend()->getValue($_customer);
                            $customer[$attributeCode] = $value;
                        }
                        catch (Exception $e) {
                            $customer['mocoauto_api_error'] = 'customer attribute: ' . $attributeCode . ' - ' . $e->getMessage();
                        }
                        break;
                }
            }

        $taxClassId = $_customer->getTaxClassId();
        $taxClass = Mage::getModel('tax/class')->load($taxClassId);
        $customer['moco_customer_tax_class'] = $taxClass->getClassName();


        $customers[] = $customer;
        }

        $this->getResponse()
            ->setBody(json_encode($customers))
            ->setHttpResponseCode(200)
            ->setHeader('Content-type', 'application/json', true);
        return $this;
    }



    public function excustomersAction()
    {
        if(!$this->_authorise()) {
            return $this;
        }

        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));

        $offset = $this->getRequest()->getParam('offset', 0);
        $page_size = $this->getRequest()->getParam('page_size', 20);
        $since = $this->getRequest()->getParam('since', 'ALL');

        $_customerCol = Mage::getModel('customer/customer')->getCollection()->addAttributeToSelect('*');
        $_customerCol->getSelect()->limit($page_size, ($offset * $page_size))->order('updated_at');

        if($since != 'ALL'){    
            $_customerCol->addAttributeToFilter('updated_at', array('gteq' =>$since));
        }

        $customers = array();

        foreach($_customerCol as $_customer) {
            $customers[] = $_customer->toArray();
        }

        $this->getResponse()
            ->setBody(json_encode($customers))
            ->setHttpResponseCode(200)
            ->setHeader('Content-type', 'application/json', true);
        return $this;
    }

    public function categoriesAction()
    {
        if(!$this->_authorise()) {
            return $this;
        }

        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));

        $offset = $this->getRequest()->getParam('offset', 0);
        $page_size = $this->getRequest()->getParam('page_size', 20);
        $since = $this->getRequest()->getParam('since', 'ALL');

//	Need to set store to admin so as to get all web site products.
        Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

        $_categoryCol = Mage::getModel('catalog/category')->getCollection()->addAttributeToSelect('*');
        $_categoryCol->getSelect()->limit($page_size, ($offset * $page_size))->order('updated_at');
        
        if($since != 'ALL'){    
            $_categoryCol->addAttributeToFilter('updated_at', array('gteq' =>$since));
        }

        $categories = array();

        foreach($_categoryCol as $_category) {
            $categories[] = $_category->toArray();
        }

        $this->getResponse()
            ->setBody(json_encode($categories))
            ->setHttpResponseCode(200)
            ->setHeader('Content-type', 'application/json', true);
        return $this;
    }


    public function ex_productsAction()
    {
        if(!$this->_authorise()) {
            return $this;
        }

        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));

        $offset = $this->getRequest()->getParam('offset', 0);
        $page_size = $this->getRequest()->getParam('page_size', 20);
        $since = $this->getRequest()->getParam('since', 'ALL');
        $gTE = $this->getRequest()->getParam('gte', 'ALL');

        $_productCol = Mage::getModel('catalog/product')->getCollection()->addAttributeToSelect('*');

        if($since != 'ALL'){    
           $_productCol->addAttributeToFilter('updated_at', array('gteq' =>$since));
        }

        if($gTE != 'ALL'){
           $_productCol->addFieldToFilter('entity_id', array('gteq' =>$gTE));
           $_productCol->getSelect()->limit($page_size, ($offset * $page_size))->order('entity_id');
        }
        else{
           $_productCol->getSelect()->limit($page_size, ($offset * $page_size))->order('updated_at');
        }

        $products[] = array('success' => 'true');        


        //Mage::log((string) $_productCol->getSelect());

        foreach($_productCol as $_product){

        // get all the custom attributes of the product
            $attributes = $_product->getAttributes();
        
            foreach ($attributes as $attribute) {      
                $attributeCode = $attribute->getAttributeCode();        
                
                switch ($attributeCode){
                    case 'in_depth':
		        break;
                    case 'description':
                        break;
                    case 'short_description':
                        break;
                    case 'thumbnail':
                        break;
                    case 'small_image':
                        break;
                    case 'image':
                        break;
                    default:
                        try {
                            $value = $attribute->getFrontend()->getValue($_product);
                            $products[] = array($attributeCode => $value); 
                        }
                        catch (Exception $e) {
                            $products[] = array('mocoauto_api_error' => 'product attribute ' . $attributeCode . ' ' . $e->getMessage());
                        }
                        break;
                }
            }   
        
// Get full url to product image

            try{
                $full_path_url = (string)Mage::helper('catalog/image')->init($_product, 'thumbnail');
                $products[] = array('thumbnail' => $full_path_url);
                $full_path_url = (string)Mage::helper('catalog/image')->init($_product, 'small_image');
                $products[] = array('small_image' => $full_path_url);
                $full_path_url = (string)Mage::helper('catalog/image')->init($_product, 'image');
                $products[] = array('image' => $full_path_url);
            }
            catch (Exception $e) {
                $products[] = array('mocoauto_api_error' => 'full path to image error:' . $e->getMessage());
            }
 
// get all the categories of the product

            $categories = $_product->getCategoryCollection()->addAttributeToSelect('name');
        
            foreach ($categories as $category) {      
                $products[] = array('moco_category' => $category->getID());
            }

// get inventory information

            try{
                $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($_product);

                $products[] = array('stock_managed' => $stock->getManageStock());
                $products[] = array('stock_availability' => $stock->getIsInStock());
            }
            catch (Exception $e) {
                $products[] = array('mocoauto_api_error' => 'moco_product_inventory: ' . $e->getMessage());
            }

// if type is configurable get simple product children

            if($_product->getTypeID() == 'configurable'){
                //$assocProducts = Mage::getModel('catalog/product_type_configurable')->getUsedProducts(null,$_product);
                $assocProducts = $_product->getTypeInstance()->getUsedProducts();

                foreach($assocProducts as $assocProduct){
                    $products[] = array('childProductID' => $assocProduct->getID());
                }  
            }

// if type is grouped get associated product children

            if($_product->getTypeID() == 'grouped'){

                $groupedProducts = $_product->getTypeInstance(true)->getAssociatedProducts($_product);

                foreach($groupedProducts as $groupedProduct){
                    $products[] = array('childProductID' => $groupedProduct->getID());

                }  
            }

// write end of record mark
           $products[] = array('moco_end_of_record' => 'True');

        }
        
        $this->getResponse()
            ->setBody(json_encode(array('products' => $products)))
            ->setHttpResponseCode(200)
            ->setHeader('Content-type', 'application/json', true);
        return $this;
    }

    public function product_idsAction()
    {
        if(!$this->_authorise()) {
            return $this;
        }

        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));

        $offset = $this->getRequest()->getParam('offset', 0);
        $page_size = $this->getRequest()->getParam('page_size', 20);

        Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
        $productIds = Mage::getModel('catalog/product')->getCollection()->getAllIds($limit= $page_size, $offset =($offset * $page_size));

        $this->getResponse()
            ->setBody(json_encode($productIds))
            ->setHttpResponseCode(200)
            ->setHeader('Content-type', 'application/json', true);
        return $this;
    }


    public function productsAction()
    {
        if(!$this->_authorise()) {
            return $this;
        }

        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));

        $offset = $this->getRequest()->getParam('offset', 0);
        $page_size = $this->getRequest()->getParam('page_size', 20);
        $since = $this->getRequest()->getParam('since', 'ALL');
        $gTE = $this->getRequest()->getParam('gte', 'ALL');

//	Need to set store to admin so as to get all web site products.
        Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

        $_productCol = Mage::getModel('catalog/product')->getCollection()->addAttributeToSelect('*');

        if($since != 'ALL'){    
           $_productCol->addAttributeToFilter('updated_at', array('gteq' =>$since));
        }

        if($gTE != 'ALL'){
           $_productCol->addFieldToFilter('entity_id', array('gteq' =>$gTE));
           $_productCol->getSelect()->limit($page_size, ($offset * $page_size))->order('entity_id');
        }
        else{
           $_productCol->getSelect()->limit($page_size, ($offset * $page_size))->order('updated_at');
        }

        $products[] = array('success' => 'true');        


        //Mage::log((string) $_productCol->getSelect());

        foreach($_productCol as $_product){

        // get all the custom attributes of the product
            $attributes = $_product->getAttributes();
        
            foreach ($attributes as $attribute) {      
                $attributeCode = $attribute->getAttributeCode();        
                
                switch ($attributeCode){
                    case 'in_depth':
		        break;
                    case 'description':
                        break;
                    case 'short_description':
                        break;
                    case 'thumbnail':
                        break;
                    case 'small_image':
                        break;
                    case 'image':
                        break;
                    default:
                        try {
                            $value = $attribute->getFrontend()->getValue($_product);
                            $products[] = array($attributeCode => $value); 
                        }
                        catch (Exception $e) {
                            $products[] = array('mocoauto_api_error' => 'product attribute ' . $attributeCode . ' ' . $e->getMessage());
                        }
                        break;
                }
            }   
        
// Get full url to product image

            try{
                $full_path_url = (string)Mage::helper('catalog/image')->init($_product, 'thumbnail');
                $products[] = array('thumbnail' => $full_path_url);
                $full_path_url = (string)Mage::helper('catalog/image')->init($_product, 'small_image');
                $products[] = array('small_image' => $full_path_url);
                $full_path_url = (string)Mage::helper('catalog/image')->init($_product, 'image');
                $products[] = array('image' => $full_path_url);
            }
            catch (Exception $e) {
                $products[] = array('mocoauto_api_error' => 'full path to image error:' . $e->getMessage());
            }
 
// get all the categories of the product

            $categories = $_product->getCategoryCollection()->addAttributeToSelect('name');
        
            foreach ($categories as $category) {      
                $products[] = array('moco_category' => $category->getID());
            }

// get inventory information

            try{
                $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($_product);

                $products[] = array('stock_managed' => $stock->getManageStock());
                $products[] = array('stock_availability' => $stock->getIsInStock());
            }
            catch (Exception $e) {
                $products[] = array('mocoauto_api_error' => 'moco_product_inventory: ' . $e->getMessage());
            }

// if type is configurable get simple product children

            if($_product->getTypeID() == 'configurable'){
                //$assocProducts = Mage::getModel('catalog/product_type_configurable')->getUsedProducts(null,$_product);
                $assocProducts = $_product->getTypeInstance()->getUsedProducts();

                foreach($assocProducts as $assocProduct){
                    $products[] = array('childProductID' => $assocProduct->getID());
                }  
            }

// if type is grouped get associated product children

            if($_product->getTypeID() == 'grouped'){

                $groupedProducts = $_product->getTypeInstance(true)->getAssociatedProducts($_product);

                foreach($groupedProducts as $groupedProduct){
                    $products[] = array('childProductID' => $groupedProduct->getID());

                }  
            }

// write end of record mark
           $products[] = array('moco_end_of_record' => 'True');

        }
        
        $this->getResponse()
            ->setBody(json_encode(array('products' => $products)))
            ->setHttpResponseCode(200)
            ->setHeader('Content-type', 'application/json', true);
        return $this;
    }


    public function stocklevelsAction()
    {
        if(!$this->_authorise()) {
            return $this;
        }

        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));

        $offset = $this->getRequest()->getParam('offset', 0);
        $page_size = $this->getRequest()->getParam('page_size', 1000);

        $_productCollection = Mage::getModel('cataloginventory/stock_item')->getCollection()->addFieldToFilter('qty', array("neq" => 0));
        $_productCollection->getSelect()->limit($page_size, ($offset * $page_size))->order('product_id');

        $stocklevels = array();
        $stocklevels[] = array('success' => 'true');

        foreach($_productCollection as $_product){
            $stocklevels[] = array(($_product->getOrigData('product_id')) => $_product->getQty());
        }


        $this->getResponse()
            ->setBody(json_encode(array('stocklevels' => $stocklevels)))
            ->setHttpResponseCode(200)
            ->setHeader('Content-type', 'application/json', true);
        return $this;
    }



    public function log_urlAction()
    {
        $tablename = 'log_url';     // Set the table name here
    
        if(!$this->_authorise()) {
            return $this;
        }

        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));

        $offset = $this->getRequest()->getParam('offset', 0);
        $page_size = $this->getRequest()->getParam('page_size', 20);
        $since = $this->getRequest()->getParam('since', 'ALL');

        $_read = Mage::getSingleton('core/resource')->getConnection('core_read');

        if(!$_read ->isTableExists($tablename)){    //Table does not exist
            $readresults=array($tablename ." table does not exist"); 
        }           
        else{
            $query = 'select * from ' . $tablename . ' limit ' . $offset . ',' . $page_size;
            $readresults = $_read->fetchAll($query);
        }

        $this->getResponse()
            ->setBody(json_encode($readresults))
            ->setHttpResponseCode(200)
            ->setHeader('Content-type', 'application/json', true);
        return $this;
    }

    public function log_url_infoAction()
    {
        $tablename = 'log_url_info';         // Set the table name here

        if(!$this->_authorise()) {
            return $this;
        }

        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));

        $offset = $this->getRequest()->getParam('offset', 0);
        $page_size = $this->getRequest()->getParam('page_size', 20);
        $since = $this->getRequest()->getParam('since', 'ALL');

        $_read = Mage::getSingleton('core/resource')->getConnection('core_read');

        if(!$_read ->isTableExists($tablename)){    //Table does not exist
            $readresults=array($tablename ." table does not exist"); 
        }           
        else{
            $query = 'select * from ' . $tablename . ' limit ' . $offset . ',' . $page_size;
            $readresults = $_read->fetchAll($query);
        }

        $this->getResponse()
            ->setBody(json_encode($readresults))
            ->setHttpResponseCode(200)
            ->setHeader('Content-type', 'application/json', true);
        return $this;

    }

    public function log_url_joinedAction()
    {
        $tablename1 = 'log_url';         // Set the table name here
        $tablename2 = 'log_url_info';         // Set the table name here

        if(!$this->_authorise()) {
            return $this;
        }

        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));

        $offset = $this->getRequest()->getParam('offset', 0);
        $page_size = $this->getRequest()->getParam('page_size', 20);
        $since = $this->getRequest()->getParam('since', 'ALL');

        $_read = Mage::getSingleton('core/resource')->getConnection('core_read');

        if(!$_read ->isTableExists($tablename1)){    //Table does not exist
            $readresults=array($tablename1 ." table does not exist");
        }
        elseif(!$_read ->isTableExists($tablename2)){    //Table does not exist
            $readresults=array($tablename2 ." table does not exist");
        }
        else{
            $query = 'select visitor_id, visit_time, url, referer from ' . $tablename1 .
            ' Left join ' . $tablename2 . ' on ' . $tablename1 . '.url_id = ' . $tablename2 . '.url_id where url not like "%mocoauto%"';

            if($since != 'ALL'){
                $query = $query . ' and visit_time > "' . $since . '"';
            }

            $query = $query .' limit ' . $offset . ',' . $page_size;

            $readresults = $_read->fetchAll($query);
        }
    }

    public function log_all_joinedAction()
    {
        $tablename1 = 'log_url';
        $tablename2 = 'log_url_info';
        $tablename3 = 'log_visitor';    
        $tablename4 = 'log_visitor_info';


        if(!$this->_authorise()) {
            return $this;
        }

        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));

        $offset = $this->getRequest()->getParam('offset', 0);
        $page_size = $this->getRequest()->getParam('page_size', 20);
        $since = $this->getRequest()->getParam('since', 'ALL');

        try{
            $_read = Mage::getSingleton('core/resource')->getConnection('core_read');
            if(!$_read ->showTableStatus(trim($tablename1,"'"))){
                $readresults=array($tablename1 ." table does not exist");
            } 
            elseif(!$_read ->showTableStatus(trim($tablename2,"'"))){
                $readresults=array($tablename2 ." table does not exist");
            }
            elseif(!$_read ->showTableStatus(trim($tablename3,"'"))){
                $readresults=array($tablename3 ." table does not exist");
            }
            elseif(!$_read ->showTableStatus(trim($tablename4,"'"))){
                $readresults=array($tablename4 ." table does not exist");
            }
            else{
                $query = 'select '; 
                $query = $query . $tablename1 . '.url_id, ' . $tablename1 . '.visitor_id, visit_time,';	    //log_url
                $query = $query . ' url, referer,';                                                         //log_url_info
	        $query = $query . ' session_id, first_visit_at, last_visit_at, store_id,';                  //log_visitor
                $query = $query . ' http_referer, http_user_agent, server_addr, remote_addr';               //log_visitor_info
                $query = $query . ' from ' . $tablename1;
                $query = $query . ' Left join ' . $tablename2 . ' on ' . $tablename1 . '.url_id = ' . $tablename2 . '.url_id';
                $query = $query . ' Left join ' . $tablename3 . ' on ' . $tablename1 . '.visitor_id = ' . $tablename3 . '.visitor_id';
                $query = $query . ' Left join ' . $tablename4 . ' on ' . $tablename1 . '.visitor_id = ' . $tablename4 . '.visitor_id where url not like "%mocoauto%"';

                if($since != 'ALL'){
                    $query = $query . ' and visit_time > "' . $since . '"';
                }

                $query = $query .' limit ' . $offset . ',' . $page_size;

                //Mage::log('DBG SQL: '. $query);
                $readresults = $_read->fetchAll($query);
            }
        }
        catch(Exception $e) {
                $readresults[] = array('mocoauto_api_error' => 'error reading logs all joined: ' . $e->getMessage());
        }

        $this->getResponse()
            ->setBody(json_encode($readresults))
            ->setHttpResponseCode(200)
            ->setHeader('Content-type', 'application/json', true);
        return $this;
    }
    public function ex_log_all_joinedAction()
    {
        $tablename1 = 'log_url';
        $tablename2 = 'log_url_info';
        $tablename3 = 'log_visitor';    
        $tablename4 = 'log_visitor_info';


        if(!$this->_authorise()) {
            return $this;
        }

        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));

        $offset = $this->getRequest()->getParam('offset', 0);
        $page_size = $this->getRequest()->getParam('page_size', 20);
        $since = $this->getRequest()->getParam('since', 'ALL');

        $_read = Mage::getSingleton('core/resource')->getConnection('core_read');

        if(!$_read ->isTableExists($tablename1)){    //Table does not exist
            $readresults=array($tablename1 ." table does not exist");
        }
        elseif(!$_read ->isTableExists($tablename2)){    //Table does not exist
            $readresults=array($tablename2 ." table does not exist");
        }
        else{
            $query = 'select '; 
            $query = $query . $tablename1 . '.url_id, ' . $tablename1 . '.visitor_id, visit_time,';	//log_url
            $query = $query . ' url, referer,';                                                         //log_url_info
	    $query = $query . ' session_id, first_visit_at, last_visit_at, store_id,';                  //log_visitor
            $query = $query . ' http_referer, http_user_agent, server_addr, remote_addr';               //log_visitor_info
            $query = $query . ' from ' . $tablename1;
            $query = $query . ' Left join ' . $tablename2 . ' on ' . $tablename1 . '.url_id = ' . $tablename2 . '.url_id';
            $query = $query . ' Left join ' . $tablename3 . ' on ' . $tablename1 . '.visitor_id = ' . $tablename3 . '.visitor_id';
            $query = $query . ' Left join ' . $tablename4 . ' on ' . $tablename1 . '.visitor_id = ' . $tablename4 . '.visitor_id where url not like "%mocoauto%"';

            if($since != 'ALL'){
                $query = $query . ' and visit_time > "' . $since . '"';
            }

            $query = $query .' limit ' . $offset . ',' . $page_size;

            //Mage::log('DBG SQL: '. $query);
            $readresults = $_read->fetchAll($query);
        }

        $this->getResponse()
            ->setBody(json_encode($readresults))
            ->setHttpResponseCode(200)
            ->setHeader('Content-type', 'application/json', true);
        return $this;
    }

    public function log_visitorAction()
    {
        $tablename = 'log_visitor';         // Set the table name here

        if(!$this->_authorise()) {
            return $this;
        }

        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));

        $offset = $this->getRequest()->getParam('offset', 0);
        $page_size = $this->getRequest()->getParam('page_size', 20);
        $since = $this->getRequest()->getParam('since', 'ALL');

        $_read = Mage::getSingleton('core/resource')->getConnection('core_read');

        if(!$_read ->isTableExists($tablename)){    //Table does not exist
            $readresults=array($tablename ." table does not exist"); 
        }           
        else{
            $query = 'select * from ' . $tablename . ' limit ' . $offset . ',' . $page_size;
            $readresults = $_read->fetchAll($query);
        }

        $this->getResponse()
            ->setBody(json_encode($readresults))
            ->setHttpResponseCode(200)
            ->setHeader('Content-type', 'application/json', true);
        return $this;
    }

    public function log_visitor_infoAction()
    {
        $tablename = 'log_visitor_info';         // Set the table name here

        if(!$this->_authorise()) {
            return $this;
        }

        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));

        $offset = $this->getRequest()->getParam('offset', 0);
        $page_size = $this->getRequest()->getParam('page_size', 20);
        $since = $this->getRequest()->getParam('since', 'ALL');

        $_read = Mage::getSingleton('core/resource')->getConnection('core_read');

        if(!$_read ->isTableExists($tablename)){    //Table does not exist
            $readresults=array($tablename ." table does not exist"); 
        }           
        else{
            $query = 'select * from ' . $tablename . ' limit ' . $offset . ',' . $page_size;
            $readresults = $_read->fetchAll($query);
        }

        $this->getResponse()
            ->setBody(json_encode($readresults))
            ->setHttpResponseCode(200)
            ->setHeader('Content-type', 'application/json', true);
        return $this;
   }


    public function log_visitor_joinedAction()
    {
        $tablename1 = 'log_visitor';         // Set the table name here
        $tablename2 = 'log_visitor_info';         // Set the table name here

        if(!$this->_authorise()) {
            return $this;
        }

        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));

        $offset = $this->getRequest()->getParam('offset', 0);
        $page_size = $this->getRequest()->getParam('page_size', 20);
        $since = $this->getRequest()->getParam('since', 'ALL');

        $_read = Mage::getSingleton('core/resource')->getConnection('core_read');

        if(!$_read ->isTableExists($tablename1)){    //Table does not exist
            $readresults=array($tablename1 ." table does not exist");
        }
        elseif(!$_read ->isTableExists($tablename2)){    //Table does not exist
            $readresults=array($tablename2 ." table does not exist");
        }
        else{
            $query = 'select ' .
            $tablename1 . '.visitor_id, session_id, first_visit_at, last_visit_at, last_url_id, store_id, http_referer, http_user_agent, remote_addr from '
             . $tablename1 . ' Left join ' . $tablename2 . ' on ' . $tablename1 . '.visitor_id = ' . $tablename2 . '.visitor_id';

            if($since != 'ALL'){
                $query = $query . ' where last_vist_at > "' . $since . '"';
            }

            $query = $query .' limit ' . $offset . ',' . $page_size;

            $readresults = $_read->fetchAll($query);
        }

        $this->getResponse()
            ->setBody(json_encode($readresults))
            ->setHttpResponseCode(200)
            ->setHeader('Content-type', 'application/json', true);
        return $this;
    }

    public function log_customerAction()
    {
        $tablename = 'log_customer';         // Set the table name here

        if(!$this->_authorise()) {
            return $this;
        }

        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));

        $offset = $this->getRequest()->getParam('offset', 0);
        $page_size = $this->getRequest()->getParam('page_size', 20);
        $since = $this->getRequest()->getParam('since', 'ALL');

        $_read = Mage::getSingleton('core/resource')->getConnection('core_read');

        if(!$_read ->isTableExists($tablename)){    //Table does not exist
            $readresults=array($tablename ." table does not exist"); 
        }           
        else{
            $query = 'select * from ' . $tablename;

            if($since != 'ALL'){
                $query = $query . ' where login_at > "' . $since . '"';
            }

            $query = $query .' limit ' . $offset . ',' . $page_size;

            $readresults = $_read->fetchAll($query);
        }

        $this->getResponse()
            ->setBody(json_encode($readresults))
            ->setHttpResponseCode(200)
            ->setHeader('Content-type', 'application/json', true);
        return $this;
    }

    public function ex_subscribersAction()
    {
        if(!$this->_authorise()) {
            return $this;
        }

        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));

        $offset = $this->getRequest()->getParam('offset', 0);
        $page_size = $this->getRequest()->getParam('page_size', 20);
        $since = $this->getRequest()->getParam('since', 'ALL');

        $_subscribersCol = Mage::getModel('newsletter/subscriber')->getCollection(); //->addAttributeToSelect('*');
        $_subscribersCol->getSelect()->limit($page_size, ($offset * $page_size));    //->order('updated_at');

        $subscribers = array();

        foreach($_subscribersCol as $_subscriber) {
            $subscribers[] = $_subscriber->toArray();
        }

        $this->getResponse()
            ->setBody(json_encode($subscribers))
            ->setHttpResponseCode(200)
            ->setHeader('Content-type', 'application/json', true);
        return $this;
    }

    public function subscribersAction()
    {
/*      STATUS_NOT_ACTIVE = 2
        STATUS_SUBSCRIBED = 1
        STATUS_UNCONFIRMED = 4
        STATUS_UNSUBSCRIBED = 3                        */
        if(!$this->_authorise()) {
            return $this;
        }

        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));

        $offset = $this->getRequest()->getParam('offset', 0);
        $page_size = $this->getRequest()->getParam('page_size', 20);
        $since = $this->getRequest()->getParam('since', 'ALL');
        $subscriber_status= $this->getRequest()->getParam('status', 'ALL');
        $gTE = $this->getRequest()->getParam('gte', 'ALL');

        $_subscribersCol = Mage::getModel('newsletter/subscriber')->getCollection(); 
        $_subscribersCol->getSelect()->limit($page_size, ($offset * $page_size));   

        if($subscriber_status != 'ALL'){
           $_subscribersCol->addFieldToFilter('subscriber_status', $subscriber_status);
        }

        if($gTE != 'ALL'){
            $_subscribersCol->addFieldToFilter('subscriber_id', array('gteq' =>$gTE));
        }

        $subscribers = array();

        foreach($_subscribersCol as $_subscriber) {
            $subscribers[] = $_subscriber->toArray();
        }

        $this->getResponse()
            ->setBody(json_encode($subscribers))
            ->setHttpResponseCode(200)
            ->setHeader('Content-type', 'application/json', true);
        return $this;
    }

    public function storesAction()
    {
        if(!$this->_authorise()) {
            return $this;
        }

        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));

        $stores = array();

        foreach (Mage::app()->getWebsites() as $_website) {
          foreach ($_website->getGroups() as $group) {
            $stores[] = array('website name' => $_website->getName(), 'website Id' => $_website->getId());
            $_stores = $group->getStores();
            foreach ($_stores as $_store) {
              $storeInfo = $_store->toArray();
              $storeID = $_store->getStoreId();
              $storeInfo['tax/calculation/price_includes_tax'] = Mage::getStoreConfig('tax/calculation/price_includes_tax', $storeID);
              $storeInfo['tax/defaults/country'] = Mage::getStoreConfig('tax/defaults/country', $storeID);
              $storeInfo['URL_TYPE_DIRECT_LINK'] =  Mage::app()->getStore($storeID)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_DIRECT_LINK);
              $storeInfo['URL_TYPE_JS'] =  Mage::app()->getStore($storeID)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_JS);
              $storeInfo['URL_TYPE_LINK'] =  Mage::app()->getStore($storeID)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);
              $storeInfo['URL_TYPE_MEDIA'] =  Mage::app()->getStore($storeID)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA);
              $storeInfo['URL_TYPE_SKIN'] =  Mage::app()->getStore($storeID)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_SKIN);
              $storeInfo['URL_TYPE_WEB'] =  Mage::app()->getStore($storeID)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);

              $stores[] = $storeInfo;
            }
          }
        }   

        $this->getResponse()
            ->setBody(json_encode($stores))
            ->setHttpResponseCode(200)
            ->setHeader('Content-type', 'application/json', true);
        return $this;
    }

    public function unconvertedcartsAction()
    {
        if(!$this->_authorise()) {
            return $this;
        }

        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));

        $offset = $this->getRequest()->getParam('offset', 0);
        $page_size = $this->getRequest()->getParam('page_size', 20);
        $since = $this->getRequest()->getParam('since', 'ALL');
        $gTE = $this->getRequest()->getParam('gte', 'ALL');

        $_cartsCol = Mage::getResourceModel('sales/quote_collection')->addFieldToFilter('is_active', '1'); // 1 = quote has not been conveted to an order

        $magentoVersion = Mage::getVersion();
        if (version_compare($magentoVersion, '1.7', '>=')){
            //Mage::log("Version is above check"); 
            $aboveVersion17Flag = 1;
        } 
        else {
            //Mage::log("Version is below check");             
            $aboveVersion17Flag = 0;
        }

        if($aboveVersion17Flag){                                          // This will only work with Magento > 1.6
            $_cartsCol->addFieldToFilter(                                 // If there is no email or customer id we dont want the cart.
                        array(
                            'customer_id',                                //attribute_1 with key 0
                            'customer_email',                             //attribute_2 with key 1
                        ),
                        array(
                              array('notnull'=>1),                        // This form creates a NOT NULL query. 
                              array('notnull'=>1),
                        )
                    );
        }

        $_cartsCol->getSelect()->limit($page_size, ($offset * $page_size))->order('updated_at');

        if($since != 'ALL'){
            $_cartsCol->addFieldToFilter('updated_at', array('gteq' =>$since)); // If no date filter include empty carts
        }
        else{
           $_cartsCol->addFieldToFilter('items_count', array('neq' => 0));     // If date filter supplied only include carts with items
        }

        // If using gte we want to sort by entity id
       
        if($gTE != 'ALL'){
           $_cartsCol->addFieldToFilter('entity_id', array('gteq' =>$gTE));    // If gte set include records GTE gte
        }

        //Mage::log((string) $_cartsCol->getSelect());

        $carts = array();


        foreach($_cartsCol as $_cart) {
            $cart = array();

            try {
                $cart['moco_start_of_cart_record'] = 'True';
                $cartdetails = array();

                if(!$aboveVersion17Flag && !$_cart->getCustomerId() && !$_cart->getCustomerEmail()){
                    //Mage::log($_cart->getEntityId() . " " . $_cart->getCustomerEmail() . " " .  $_cart->getCustomerId());
                    $cart['moco_no_cart_identification_information'] = 'True';
                }
                else{
                    $cartdetails = $_cart->toArray();

                    foreach ($cartdetails as $key =>$value){
                        $cart[$key] = $value;
                    }

                    $_cartItemsCol = $_cart -> getItemsCollection();
                    $cartitems = array();

                    foreach($_cartItemsCol as $_cartitem){
                        $cartitem = array();
                        try{
                            $cartitem['item_id']              = $_cartitem->getItemId();
                            $cartitem['parent_id']            = $_cartitem->getParentId();
                            $cartitem['product_id']           = $_cartitem->getProductId();
                            $cartitem['product_sku']          = $_cartitem->getSku();
                            $cartitem['product_qty']          = $_cartitem->getQty();
                            $cartitem['updated_at']           = $_cartitem->getUpdatedAt();
                            $cartitem['product_name']         = $_cartitem->getName();
                            $cartitem['product_type']         = $_cartitem->getProductType();
                            $cartitem['base_price']           = $_cartitem->getBasePrice();
                            $cartitem['base_tax_amount']      = $_cartitem->getBaseTaxAmount();
                            $cartitem['base_discount_amount'] = $_cartitem->getBaseDiscountAmount();
                            $cartitem['base_cost']            = $_cartitem->getBaseCost();
                            $cartitem['base_price_incl_tax']  = $_cartitem->getBasePriceInclTax();
                        }
                        catch(Exception $e) {
                            $cartitem['mocoauto_api_error'] = 'moco_unable_to_read_cartitem: ' . $e->getMessage();
                        }

                        $cartitems[] = $cartitem;
                    }
 
                    $cart['moco_cart_items'] = $cartitems;
                }

                $cart['moco_end_of_cart_record'] = 'True';

            }
            catch(Exception $e) {
                $cart['mocoauto_api_error'] = 'moco_unable_to_read_cart: ' . $e->getMessage();
            }

            $carts[] = $cart;

        }

        $this->getResponse()
            ->setBody(json_encode($carts))
            ->setHttpResponseCode(200)
            ->setHeader('Content-type', 'application/json', true);
        return $this;
    }
    public function test_unconvertedcartsAction()
    {
        if(!$this->_authorise()) {
            return $this;
        }

        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));

        $offset = $this->getRequest()->getParam('offset', 0);
        $page_size = $this->getRequest()->getParam('page_size', 20);
        $since = $this->getRequest()->getParam('since', 'ALL');
        $gTE = $this->getRequest()->getParam('gte', 'ALL');

        $_cartsCol = Mage::getResourceModel('sales/quote_collection')->addFieldToFilter('is_active', '1'); // 1 = quote has not been conveted to an order

        $magentoVersion = Mage::getVersion();
        if (version_compare($magentoVersion, '1.7', '>=')){
            //Mage::log("Version is above check"); 
            $aboveVersion17Flag = 1;
        } 
        else {
            //Mage::log("Version is below check");             
            $aboveVersion17Flag = 0;
        }

        if($aboveVersion17Flag){                                          // This will only work with Magento > 1.6
            $_cartsCol->addFieldToFilter(                                 // If there is no email or customer id we dont want the cart.
                        array(
                            'customer_id',                                //attribute_1 with key 0
                            'customer_email',                             //attribute_2 with key 1
                        ),
                        array(
                              array('notnull'=>1),                        // This form creates a NOT NULL query. 
                              array('notnull'=>1),
                        )
                    );
        }

        if($since != 'ALL'){
            $_cartsCol->addFieldToFilter('updated_at', array('gteq' =>$since)); // If no date filter include empty carts
        }
        else{
           $_cartsCol->addFieldToFilter('items_count', array('neq' => 0));     // If date filter supplied only include carts with items
        }

        // If using gte we want to sort by entity id
       
        if($gTE != 'ALL'){
           $_cartsCol->addFieldToFilter('entity_id', array('gteq' =>$gTE));    // If gte set include records GTE gte
           $_cartsCol->getSelect()->limit($page_size, ($offset * $page_size))->order('entity_id');
        }
        else{
           $_cartsCol->getSelect()->limit($page_size, ($offset * $page_size))->order('updated_at');
        }

        //Mage::log((string) $_cartsCol->getSelect());

        $carts = array();


        foreach($_cartsCol as $_cart) {
            $cart = array();

            try {
                $cart['moco_start_of_cart_record'] = 'True';
                $cartdetails = array();

                if(!$aboveVersion17Flag && !$_cart->getCustomerId() && !$_cart->getCustomerEmail()){
                    //Mage::log($_cart->getEntityId() . " " . $_cart->getCustomerEmail() . " " .  $_cart->getCustomerId());
                    $cart['moco_no_cart_identification_information'] = 'True';
                }
                else{
                    $cartdetails = $_cart->toArray();

                    foreach ($cartdetails as $key =>$value){
                        $cart[$key] = $value;
                    }

                    $_cartItemsCol = $_cart -> getItemsCollection();
                    $cartitems = array();

                    foreach($_cartItemsCol as $_cartitem){
                        $cartitem = array();
                        try{
                            $cartitem['item_id']              = $_cartitem->getItemId();
                            $cartitem['parent_id']            = $_cartitem->getParentId();
                            $cartitem['product_id']           = $_cartitem->getProductId();
                            $cartitem['product_sku']          = $_cartitem->getSku();
                            $cartitem['product_qty']          = $_cartitem->getQty();
                            $cartitem['updated_at']           = $_cartitem->getUpdatedAt();
                            $cartitem['product_name']         = $_cartitem->getName();
                            $cartitem['product_type']         = $_cartitem->getProductType();
                            $cartitem['base_price']           = $_cartitem->getBasePrice();
                            $cartitem['base_tax_amount']      = $_cartitem->getBaseTaxAmount();
                            $cartitem['base_discount_amount'] = $_cartitem->getBaseDiscountAmount();
                            $cartitem['base_cost']            = $_cartitem->getBaseCost();
                            $cartitem['base_price_incl_tax']  = $_cartitem->getBasePriceInclTax();
                        }
                        catch(Exception $e) {
                            $cartitem['mocoauto_api_error'] = 'moco_unable_to_read_cartitem: ' . $e->getMessage();
                        }

                        $cartitems[] = $cartitem;
                    }
 
                    $cart['moco_cart_items'] = $cartitems;
                }

                $cart['moco_end_of_cart_record'] = 'True';

            }
            catch(Exception $e) {
                $cart['mocoauto_api_error'] = 'moco_unable_to_read_cart: ' . $e->getMessage();
            }

            $carts[] = $cart;

        }

        $this->getResponse()
            ->setBody(json_encode($carts))
            ->setHttpResponseCode(200)
            ->setHeader('Content-type', 'application/json', true);
        return $this;
    }


    public function ex_unconvertedcartsAction()//This query returns only no empty carts when no dat filter applied
    {
        if(!$this->_authorise()) {
            return $this;
        }

        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));

        $offset = $this->getRequest()->getParam('offset', 0);
        $page_size = $this->getRequest()->getParam('page_size', 20);
        $since = $this->getRequest()->getParam('since', 'ALL');
        $entityId = $this->getRequest()->getParam('entity_id', 'ALL');

        $_cartsCol = Mage::getResourceModel('sales/quote_collection')->addFieldToFilter('is_active', '1');
        $_cartsCol->getSelect()->limit($page_size, ($offset * $page_size))->order('updated_at');

        if($since != 'ALL'){
            $_cartsCol->addFieldToFilter('updated_at', array('gteq' =>$since));
        }
        else{
           $_cartsCol->addFieldToFilter('items_count', array('neq' => 0));
        } 
  
        if($entityId != 'ALL'){
           $_cartsCol->addFieldToFilter('entity_id', $entityId);
        }

        //Mage::log((string) $_cartsCol->getSelect());

        $carts = array();

        foreach($_cartsCol as $_cart) {
            try {
                $carts[] = array('moco_start_of_cart_record' => 'True');
                $carts[] = $_cart->toArray();
                $_cartItemsCol = $_cart -> getItemsCollection();

                foreach($_cartItemsCol as $_cartitem){
                    $carts[] = array('product_id'  => $_cartitem->getProductId());
                    $carts[] = array('product_sku'  => $_cartitem->getSku());
                    $carts[] = array('product_qty' => $_cartitem->getQty());
                    $carts[] = array('updated_at'  => $_cartitem->getUpdatedAt());
                    $carts[] = array('product_type' => $_cartitem->getProductType());
                    //$carts[] = $_cartitem->toArray();
                }
                $carts[] = array('moco_end_of_cart_record' => 'True');
            }
            catch(Exception $e) {
                    $carts[] = array('mocoauto_api_error' => 'moco_unable_to_read_cart: ' . $e->getMessage());
            }
        }

        $this->getResponse()
            ->setBody(json_encode($carts))
            ->setHttpResponseCode(200)
            ->setHeader('Content-type', 'application/json', true);
        return $this;
    }

    public function wishlistsAction()
    {
        if(!$this->_authorise()) {
            return $this;
        }

        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));

        $offset = $this->getRequest()->getParam('offset', 0);
        $page_size = $this->getRequest()->getParam('page_size', 20);
        $since = $this->getRequest()->getParam('since', 'ALL');
        $gTE = $this->getRequest()->getParam('gte', 'ALL');

        $_wishlistCol = Mage::getModel('wishlist/wishlist')-> getCollection();

        if($since != 'ALL'){
           $_wishlistCol->addFieldToFilter('updated_at', array('gteq' =>$since));
        }

        if($gTE != 'ALL'){
            $_wishlistCol->addFieldToFilter('wishlist_id', array('gteq' =>$gTE));
            $_wishlistCol->getSelect()->limit($page_size, ($offset * $page_size))->order('wishlist_id');
        }
        else{
            $_wishlistCol->getSelect()->limit($page_size, ($offset * $page_size))->order('updated_at');
        }

        $wishlists = array();

        foreach($_wishlistCol as $_wishlist) {
            $wishlist = array();
            $wishlist['moco_start_of_wishlist_record'] = 'True';
            $wishlist['wishlist_id'] = $_wishlist->getId();
            $wishlist['customer_id'] = $_wishlist->getCustomerId();
            $wishlist['updated_at'] = $_wishlist->getUpdatedAt();
            $_wishlistitemsCol = $_wishlist->getItemCollection();
            $wishlistitems = array();

            foreach($_wishlistitemsCol as $_wishlistitem){
                $wishlistitem = array();
                $wishlistitem['item_id'] = $_wishlistitem->getId();
                $wishlistitem['store_id'] = $_wishlistitem->getStoreId();
                $wishlistitem['product_id'] = $_wishlistitem->getProductId();
                $wishlistitem['product_qty'] = $_wishlistitem->getQty();
                $wishlistitem['added_at'] = $_wishlistitem->getAddedAt();
                $wishlistitems[] = $wishlistitem;
            }

            $wishlist['wish_list_items'] = $wishlistitems;            
            $wishlist['moco_end_of_wishlist_record'] = 'True';
            $wishlists[] = $wishlist;
        }

        $this->getResponse()
            ->setBody(json_encode($wishlists))
            ->setHttpResponseCode(200)
            ->setHeader('Content-type', 'application/json', true);
        return $this;
    }

    public function ex_wishlistsAction()
    {
        if(!$this->_authorise()) {
            return $this;
        }

        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));

        $offset = $this->getRequest()->getParam('offset', 0);
        $page_size = $this->getRequest()->getParam('page_size', 20);
        $since = $this->getRequest()->getParam('since', 'ALL');

        $_wishlistCol = Mage::getModel('wishlist/wishlist')-> getCollection();
        $_wishlistCol->getSelect()->limit($page_size, ($offset * $page_size))->order('updated_at');

        if($since != 'ALL'){
           $_wishlistCol->addFieldToFilter('updated_at', array('gteq' =>$since));
        }

        $wishlists = array();

        foreach($_wishlistCol as $_wishlist) {
            $wishlists[] = array('moco_start_of_wishlist_record' => 'True');
            $wishlists[] = $_wishlist->toArray();
            $_wishlistitemsCol = $_wishlist->getItemCollection();
            foreach($_wishlistitemsCol as $_wishlistitem){
                $wishlists[] = array('wishlist_item_id'  => $_wishlistitem->getId());
                $wishlists[] = array('product_id'  => $_wishlistitem->getProductId());
                $wishlists[] = array('product_qty' => $_wishlistitem->getQty());
                $wishlists[] = array('added_at'    => $_wishlistitem->getAddedAt());
            }

            $wishlists[] = array('moco_end_of_wishlist_record' => 'True');
        }

        $this->getResponse()
            ->setBody(json_encode($wishlists))
            ->setHttpResponseCode(200)
            ->setHeader('Content-type', 'application/json', true);
        return $this;
    }

    public function installinfoAction()
    {
        if(!$this->_authorise()) {
            return $this;
        }

        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));

        $installinfo = array();

        $installinfo[] = array('moco install info' => 'True');
        $installinfo[] = array('Base URL' => Mage::getBaseUrl());
        $installinfo[] = array('Home URL' => Mage::helper('core/url')->getHomeUrl());
        $installinfo[] = array('Home URL' => Mage::getBaseDir());
        $installinfo[] = array('Media URL' => Mage::getBaseUrl('media'));

        


        $calc = Mage::getSingleton('tax/calculation');
        $rates = $calc->getRatesForAllProductTaxClasses($calc->getRateRequest());

        foreach ($rates as $class=>$rate) {
           $installinfo[] = array('Tax rate' => floatval($rate));
        }

        $this->getResponse()
            ->setBody(json_encode($installinfo))
            ->setHttpResponseCode(200)
            ->setHeader('Content-type', 'application/json', true);
        return $this;
    }

    public function rulesAction()
    {
        if(!$this->_authorise()) {
            return $this;
        }

        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));

        $offset = $this->getRequest()->getParam('offset', 0);
        $page_size = $this->getRequest()->getParam('page_size', 20);
        $since = $this->getRequest()->getParam('since', 'ALL');

        $_rulesCol = Mage::getModel('salesrule/rule')->getCollection();


        foreach ($_rulesCol as $rule) {
            // print_r($rule->getData());
            $rulelist[] = array('moco_start_of_rule_record' => 'True');
            $rulelist[] = array('rule_id' => $rule->getRule_id());
            $rulelist[] = array('rule_name' => $rule->getName());
            $rulelist[] = array('rule_description' => $rule->getDescription());
            $rulelist[] = array('rule_from_date' => $rule->getFrom_date());
            $rulelist[] = array('rule_to_date' => $rule->getTo_date());
            $rulelist[] = array('rule_is_active' => $rule->getIsActive());
            $rulelist[] = array('rule_coupon_type' => $rule->getCoupon_type());
            $rulelist[] = array('moco_end_of_rule_record' => 'True');
        }

        $this->getResponse()
            ->setBody(json_encode($rulelist))
            ->setHttpResponseCode(200)
            ->setHeader('Content-type', 'application/json', true);
        return $this;
    }

}
