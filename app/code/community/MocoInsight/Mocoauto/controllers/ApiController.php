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
//  log_all_joinedAction
//  log_customerAction()
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
//  creditsAction
//  credit_idsAction
//  invoicesAction
//  invoice_idsAction
//  sql_sales_flat_quoteAction()
//  sql_anytableAction()
//  sql_describeAction
//  sql_showtablesAction
//  list_modulesAction
//  giftcardsAction


define("apiversion","1.5.3.7");

class MocoInsight_Mocoauto_ApiController extends Mage_Core_Controller_Front_Action
{

    public function _authorise()
    {
        $tokenString = $this->getRequest()->getHeader('mocoapi');     // Get the api string from header
        $noSlashTokenStr = stripslashes($tokenString);                // Strip slashes in case of magic quotes
        $token = substr($noSlashTokenStr,8,32);                       // Cut apikey from header string 
        $apiToken = Mage::helper('mocoauto')->getApiToken(false);     // Get apikey value from plugin settings

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
                    ->setBody(json_encode(array('success' => false,
                                                'message' => 'Not authorised',
                                                'MocoAPI version' => apiversion,
                                                'key string' => $tokenString,
                                                'key extracted' => $token)))
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

    public function mocodebugAction()
    {

        $time_start = microtime(true);

        $MocoApiEpVer = '1.0.0';  // First version with version returned. 

        $currentSystemTime = date('Y-m-d H:i:s', time());
        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));
        $since = $this->getRequest()->getParam('since','ALL');
        Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID); //   set to admin view all sites and stores
        $magentoVersion = Mage::getVersion();
        $moduleversion = (String)Mage::getConfig()->getNode()->modules->MocoInsight_Mocoauto->version;
        $phpversion = phpversion();

        if(method_exists('Mage', 'getEdition')){
            $magentoedition = (String)Mage::getEdition();
        }
        else{
            $magentoedition = 'method Mage::getEdition() unavailable';
        }

        $paramsArray = $this->getRequest()->getParams();
        $headersArray = getAllHeaders();
        $keyByZend = $this->getRequest()->getHeader('mocoapi');
        $keyByPhp = $_SERVER['HTTPS_MOCOAPI'];
        if(!$keyByPhp){
            $keyByPhp = $_SERVER['HTTP_MOCOAPI'];
        }

        $resultArray = array(

            'mocoauto_api_end_point_version' => $MocoApiEpVer,
            'System Date Time' => $currentSystemTime,
            'Magento Version' => $magentoVersion,
            'Magento Edition' => $magentoedition,
            'MocoAPI Version' => apiversion,
            'Module Version' => $moduleversion,
            'PHP Version' => $phpversion,
            'API processing time' => (microtime(true) - $time_start),
            'key sent (Zend method)' => $keyByZend,
            'key sent (PHP method)' => $keyByPhp
         );

        $resultArray['Request params'] = $paramsArray;
        $resultArray['Request headers'] = $headersArray;

        $this->getResponse()
            ->setBody(json_encode($resultArray))
            ->setHttpResponseCode(200)
            ->setHeader('Content-type', 'application/json', true);
        return $this;
    }


////////////////////////////////////////////////////////////////////////////////////////////////////////
//  statsAction - returns size of data
//
//  1.5.2.2 - adding a count for invoices
//
///////////////////////////////////////////////////////////////////////////////////////////////////////
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

        $_creditCol = Mage::getModel('sales/order_creditmemo')->getCollection();
        if($since != 'ALL'){
           $_creditCol->addAttributeToFilter('updated_at', array('gteq' =>$since));
        }
        $creditcount = $_creditCol->getSize();

        $_invoiceCol = Mage::getModel('sales/order_invoice')->getCollection();
        if($since != 'ALL'){
           $_invoiceCol->addAttributeToFilter('updated_at', array('gteq' =>$since));
        }
        $invoicecount = $_invoiceCol->getSize();


    $magentoVersion = Mage::getVersion();
    $moduleversion = (String)Mage::getConfig()->getNode()->modules->MocoInsight_Mocoauto->version;
    $phpversion = phpversion();

    if(method_exists('Mage', 'getEdition')){
        $magentoedition = (String)Mage::getEdition();
    }
    else{
        $magentoedition = 'method Mage::getEdition() unavailable';
    }

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
        'Credit memos' => $creditcount,
        'Invoices' => $invoicecount,
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

    public function statsliteAction()
    {
        if(!$this->_authorise()) {
            return $this;
        }

        $time_start = microtime(true);

        $MocoApiEpVer = '1.0.0';  // First version with version returned. 

        $currentSystemTime = date('Y-m-d H:i:s', time());
        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));
        $since = $this->getRequest()->getParam('since','ALL');
        Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID); //   set to admin view all sites and stores
        $magentoVersion = Mage::getVersion();
        $moduleversion = (String)Mage::getConfig()->getNode()->modules->MocoInsight_Mocoauto->version;
        $phpversion = phpversion();

        if(method_exists('Mage', 'getEdition')){
            $magentoedition = (String)Mage::getEdition();
        }
        else{
            $magentoedition = 'method Mage::getEdition() unavailable';
        }

        $statslite = array(
            'mocoauto_api_end_point_version' => $MocoApiEpVer,
            'System Date Time' => $currentSystemTime,
            'Magento Version' => $magentoVersion,
            'Magento Edition' => $magentoedition,
            'MocoAPI Version' => apiversion,
            'Module Version' => $moduleversion,
            'PHP Version' => $phpversion,
            'API processing time' => (microtime(true) - $time_start)
         );

        $this->getResponse()
            ->setBody(json_encode($statslite))
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


    public function ex_ordersAction() 
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

        // Check if the Termnado tables exsists set flag so we read shipments later on.
        $tablename = 'temando_shipment';
        $_read = Mage::getSingleton('core/resource')->getConnection('core_read');

        if($_read ->isTableExists($tablename)){    //Table does exist
            $TEMANDO_FLAG='TRUE';
        }
        else{
            $TEMANDO_FLAG='FALSE';
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

                $_orderShipmentsCol = $_order->getShipmentsCollection();
                $ordershipments = array();
                foreach($_orderShipmentsCol as $_ordershipment){
                    $ordershipments[] = $_ordershipment->toArray();
                }
                $order['moco_shipments'] = $ordershipments;
              
 
                // If the Temanado flag is set then get any shippiong records that match the order number.
                if($TEMANDO_FLAG == 'TRUE'){    //Table does exist
                    $temandodata = array();
                    $query = 'select id, order_id, anticipated_cost, ready_date, ready_time from ' . $tablename . ' where order_id = "' . $_order->getEntityId() . '"';
                    //Mage::log('DBG SQL: '. $query);
                    $readresults = $_read->fetchAll($query);
                    $temandodata  = $readresults;
                    $order['moco_temandodata'] = $temandodata;
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
//  ordersAction - return all information on a order
//  1.5.1.9 - only request specified shippment attributes as shipping label can get very big and we don't need it or much else.
//  1.5.2.0 - request all shipment info execpet label as there are many diffrent shipping plugins.
//
    public function ordersAction() 
    {
        $MocoApiEpVer = '1.0.0';  // First version with version returned. limit to 512 bytes 
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

        // Check if the Termnado tables exsists set flag so we read shipments later on.
        $tablename = 'temando_shipment';
        $_read = Mage::getSingleton('core/resource')->getConnection('core_read');

        if($_read ->isTableExists($tablename)){    //Table does exist
            $TEMANDO_FLAG='TRUE';
        }
        else{
            $TEMANDO_FLAG='FALSE';
        }

        $orders = array();
        $orders[] = array('mocoauto_api_end_point_version' => $MocoApiEpVer);

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

                if(is_object($_order->getShipmentsCollection())){			 	// check obj exists
                    $_orderShipmentsCol = $_order->getShipmentsCollection();			// get collection of shipment objects
                    $ordershipments = array();
                    foreach($_orderShipmentsCol as $_ordershipment){				// collection of shipment objects
                        $shipmentdetails = array();							
                        $shipmentdetails = $_ordershipment->toArray();				// dump shipment object values to array
                        $ordershipment = array();
                        foreach($shipmentdetails as $key => $value){				// iterate values array
                            //Mage::log('$key = ' . $key . ' $value = '. $value);
                            if(strlen($value) < 512){                                           // if the value is less than 512 it is OK write to output array
                                $ordershipment[$key] = $value;
                            }
                        }
                        $ordershipments[] = $ordershipment;                                     // write 1 shippment detail to array with all shipments
                    }
                    $order['moco_shipments'] = $ordershipments;					// write all shipments to main output array.
                }
 
                // If the Temanado flag is set then get any shipping records that match the order number.
                if($TEMANDO_FLAG == 'TRUE'){    //Table does exist
                    $temandodata = array();
                    $query = 'select id, order_id, anticipated_cost, ready_date, ready_time from ' . $tablename . ' where order_id = "' . $_order->getEntityId() . '"';
                    //Mage::log('DBG SQL: '. $query);
                    $readresults = $_read->fetchAll($query);
                    $temandodata  = $readresults;
                    $order['moco_temandodata'] = $temandodata;
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

//  ordersAction - return all information on a order
//  1.5.1.9 - only request specified shippment attributes as shipping label can get very big and we don't need it or much else.
//  1.5.2.0 - request all shipment info execpet label as there are many diffrent shipping plugins.
//
    public function test_ordersAction()
    {
        //$MocoApiEpVer = '1.0.0';  // First version with version returned. limit to 512 bytes 
        //$MocoApiEpVer = '1.0.1';  //Return product options.  
        $MocoApiEpVer = '1.0.2';    // order by entity_id and updated_at  
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
            $_orderCol->getSelect()->limit($page_size, ($offset * $page_size))->order('updated_at')->order('entity_id');
        }

        //Mage::log('SQL Query: '.$_orderCol->getSelect());

        // Check if the Termnado tables exsists set flag so we read shipments later on.
        $tablename = 'temando_shipment';
        $_read = Mage::getSingleton('core/resource')->getConnection('core_read');

        if($_read ->isTableExists($tablename)){    //Table does exist
            $TEMANDO_FLAG='TRUE';
        }
        else{
            $TEMANDO_FLAG='FALSE';
        }

        $orders = array();
        $orders[] = array('mocoauto_api_end_point_version' => $MocoApiEpVer);
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

                if(is_object($_order->getShipmentsCollection())){                               // check obj exists
                    $_orderShipmentsCol = $_order->getShipmentsCollection();                    // get collection of shipment objects
                    $ordershipments = array();
                    foreach($_orderShipmentsCol as $_ordershipment){                            // collection of shipment objects
                        $shipmentdetails = array();
                        $shipmentdetails = $_ordershipment->toArray();                          // dump shipment object values to array
                        $ordershipment = array();
                        foreach($shipmentdetails as $key => $value){                            // iterate values array
                            //Mage::log('$key = ' . $key . ' $value = '. $value);
                            if(strlen($value) < 512){                                           // if the value is less than 512 it is OK write to output array
                                $ordershipment[$key] = $value;
                            }
                        }
                        $ordershipments[] = $ordershipment;                                     // write 1 shippment detail to array with all shipments
                    }
                    $order['moco_shipments'] = $ordershipments;                                 // write all shipments to main output array.
                }

                // If the Temanado flag is set then get any shipping records that match the order number.
                if($TEMANDO_FLAG == 'TRUE'){    //Table does exist
                    $temandodata = array();
                    $query = 'select id, order_id, anticipated_cost, ready_date, ready_time from ' . $tablename . ' where order_id = "' . $_order->getEntityId() . '"';
                    //Mage::log('DBG SQL: '. $query);
                    $readresults = $_read->fetchAll($query);
                    $temandodata  = $readresults;
                    $order['moco_temandodata'] = $temandodata;
                }


                $_orderItemsCol = $_order->getItemsCollection();
                $orderitems = array();
                foreach($_orderItemsCol as $_orderitem){
                    $orderitems[] = $_orderitem->toArray();
                    $orderitems[] = $_orderitem->getProductOptions();
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

    public function test_customersAction()
    {
        //$MocoApiEpVer = '1.0.0';  //  Include customer default billing and delivery address.
        $MocoApiEpVer = '1.0.1';    //  Include customer default billing and delivery address.


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
           $_customerCol->getSelect()->limit($page_size, ($offset * $page_size))->order('updated_at')->order('entity_id');
        }

        $customers = array();
        $customers[] = array('mocoauto_api_end_point_version' => $MocoApiEpVer);

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

            if(is_object($_customer->getPrimaryBillingAddress())){
                $_billing_address = $_customer->getPrimaryBillingAddress();
                $billaddrdetails = array();
                $billaddrdetails[] = $_billing_address->toArray();
                $customer['moco_bill_address'] = $billaddrdetails;
            }

            if(is_object($_customer->getPrimaryShippingAddress())){
                 $_shipping_address = $_customer->getPrimaryShippingAddress();
                 $shipaddrdetails = array();
                 $shipaddrdetails[] = $_shipping_address->toArray();
                 $customer['moco_ship_address'] = $shipaddrdetails;
            }

            $customers[] = $customer;
        }

        $this->getResponse()
            ->setBody(json_encode($customers))
            ->setHttpResponseCode(200)
            ->setHeader('Content-type', 'application/json', true);
        return $this;
    }

    public function customersAction()
    {
        $MocoApiEpVer = '1.0.0';  //  Include customer default billing and delivery address.

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
        $customers[] = array('mocoauto_api_end_point_version' => $MocoApiEpVer);



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
        
            if(is_object($_customer->getPrimaryBillingAddress())){
                $_billing_address = $_customer->getPrimaryBillingAddress();
                $billaddrdetails = array();
                $billaddrdetails[] = $_billing_address->toArray();
                $customer['moco_bill_address'] = $billaddrdetails;
            }

            if(is_object($_customer->getPrimaryShippingAddress())){
                 $_shipping_address = $_customer->getPrimaryShippingAddress();
                 $shipaddrdetails = array();
                 $shipaddrdetails[] = $_shipping_address->toArray();
                 $customer['moco_ship_address'] = $shipaddrdetails;
            }

            $customers[] = $customer;
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


    public function ex_productsAction()
    {
        $MocoApiEpVer = '1.0.0';  // First version with version returned. 


        if(!$this->_authorise()) {
            return $this;
        }

        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));

        $offset = $this->getRequest()->getParam('offset', 0);
        $page_size = $this->getRequest()->getParam('page_size', 20);
        $since = $this->getRequest()->getParam('since', 'ALL');
        $gTE = $this->getRequest()->getParam('gte', 'ALL');

//      Need to set store to admin so as to get all web site products.
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

        $products = array();

        //Mage::log((string) $_productCol->getSelect());

        foreach($_productCol as $_product){
            $product = array();
            $product['moco_start_of_record'] = 'True';

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
                    case 'category_ids':
                        break;
                    default:
                        try {
                            $value = $attribute->getFrontend()->getValue($_product);
                            $product[$attributeCode] = $value;
                            //Mage::log((string) $attributeCode . ':' . $value);
                        }
                        catch (Exception $e) {
                            $product['mocoauto_api_error_product_attribute'] = $attributeCode . ' ' . $e->getMessage();
                        }
                        break;
                }
            }



// Get full url to product image

            try{
                $full_path_url = (string)Mage::helper('catalog/image')->init($_product, 'thumbnail');
                $product['thumbnail'] = $full_path_url;
                $full_path_url = (string)Mage::helper('catalog/image')->init($_product, 'small_image');
                $product['small_image'] = $full_path_url;
                $full_path_url = (string)Mage::helper('catalog/image')->init($_product, 'image');
                $product['image'] = $full_path_url;
            }
            catch (Exception $e) {
                $product['mocoauto_api_error_full_path_to_image_ error:'] = $e->getMessage();
            }

// get all the categories of the product

            $categories = $_product->getCategoryCollection()->addAttributeToSelect('name');

            $mocoCategories = array();

            foreach ($categories as $category) {
                $mocoCategories[] = $category->getID();
            }
            $product['moco_categories'] = $mocoCategories;

// get inventory information
            try{
                $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($_product);

                $product['stock_managed'] = $stock->getManageStock();
                $product['stock_availability'] = $stock->getIsInStock();
                $product['stock_backorders'] = $stock->getBackorders();
            }
            catch (Exception $e) {
                $product['mocoauto_api_error_moco_product_inventory:'] = $e->getMessage();
            }

// if type is configurable get simple product children

            if($_product->getTypeID() == 'configurable'){
                //$assocProducts = Mage::getModel('catalog/product_type_configurable')->getUsedProducts(null,$_product);
                $assocProducts = $_product->getTypeInstance()->getUsedProducts();
                $childProducts = array();
                foreach($assocProducts as $assocProduct){
                    $childProducts[] = $assocProduct->getID();
                }
                $product['moco_children'] =  $childProducts;
            }

// if type is grouped get associated product children

            if($_product->getTypeID() == 'grouped'){

                $groupedProducts = $_product->getTypeInstance(true)->getAssociatedProducts($_product);
                $childProducts = array();
                foreach($groupedProducts as $groupedProduct){
                    $childProducts[] =  $groupedProduct->getID();
                }
                $product['moco_children'] =  $childProducts;
            }

           $product['moco_end_of_record'] = 'True';
           $products[] = $product;

        }


        $this->getResponse()
            ->setBody(json_encode(array('mocoauto_api_end_point_version' => $MocoApiEpVer, 'products' => $products)))
            ->setHttpResponseCode(200)
            ->setHeader('Content-type', 'application/json', true);
        return $this;
    }

    public function productsAction()
    {
     // $MocoApiEpVer = '1.0.0';  // First version with version returned. 
     // $MocoApiEpVer = '1.0.1';  // Return super attributes for configurable products.
        $MocoApiEpVer = '1.0.2';  // Sort product collection on updated_at and entity_id.


        if(!$this->_authorise()) {
            return $this;
        }

        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));

        $offset = $this->getRequest()->getParam('offset', 0);
        $page_size = $this->getRequest()->getParam('page_size', 20);
        $since = $this->getRequest()->getParam('since', 'ALL');
        $gTE = $this->getRequest()->getParam('gte', 'ALL');

//      Need to set store to admin so as to get all web site products.
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
           $_productCol->getSelect()->limit($page_size, ($offset * $page_size))->order('updated_at')->order('entity_id');
        }

        $products = array();

        // Mage::log((string) $_productCol->getSelect()); //log query

        foreach($_productCol as $_product){
            $product = array();
            $product['moco_start_of_record'] = 'True';

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
                    case 'category_ids':
                        break;
                    default:
                        try {
                            $value = $attribute->getFrontend()->getValue($_product);
                            $product[$attributeCode] = $value;
                            //Mage::log((string) $attributeCode . ':' . $value);
                        }
                        catch (Exception $e) {
                            $product['mocoauto_api_error_product_attribute'] = $attributeCode . ' ' . $e->getMessage();
                        }
                        break;
                }
            }



// Get full url to product image

            try{
                $full_path_url = (string)Mage::helper('catalog/image')->init($_product, 'thumbnail');
                $product['thumbnail'] = $full_path_url;
                $full_path_url = (string)Mage::helper('catalog/image')->init($_product, 'small_image');
                $product['small_image'] = $full_path_url;
                $full_path_url = (string)Mage::helper('catalog/image')->init($_product, 'image');
                $product['image'] = $full_path_url;
            }
            catch (Exception $e) {
                $product['mocoauto_api_error_full_path_to_image_ error:'] = $e->getMessage();
            }

// get all the categories of the product

            $categories = $_product->getCategoryCollection()->addAttributeToSelect('name');

            $mocoCategories = array();

            foreach ($categories as $category) {
                $mocoCategories[] = $category->getID();
            }
            $product['moco_categories'] = $mocoCategories;

// get inventory information
            try{
                $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($_product);

                $product['stock_managed'] = $stock->getManageStock();
                $product['stock_availability'] = $stock->getIsInStock();
                $product['stock_backorders'] = $stock->getBackorders();
            }
            catch (Exception $e) {
                $product['mocoauto_api_error_moco_product_inventory:'] = $e->getMessage();
            }

// if type is configurable get simple product children and Super Product Attributes

            if($_product->getTypeID() == 'configurable'){
                $assocProducts = $_product->getTypeInstance()->getUsedProducts();
                $childProducts = array();
                foreach($assocProducts as $assocProduct){
                    $childProducts[] = $assocProduct->getID();
                }
                $product['moco_children'] =  $childProducts;

                $superAttrs = $_product->getTypeInstance()->getConfigurableAttributesAsArray($_product);
                $moco_super_attrs = array();
                foreach($superAttrs as $superAttr){
                    $moco_super_attrs[] = $superAttr;
                }
            $product['moco_super_attrs'] =  $moco_super_attrs;
            }

// if type is grouped get associated product children

            if($_product->getTypeID() == 'grouped'){

                $groupedProducts = $_product->getTypeInstance(true)->getAssociatedProducts($_product);
                $childProducts = array();
                foreach($groupedProducts as $groupedProduct){
                    $childProducts[] =  $groupedProduct->getID();
                }
                $product['moco_children'] =  $childProducts;
            }

           $product['moco_end_of_record'] = 'True';
           $products[] = $product;

        }


        $this->getResponse()
            ->setBody(json_encode(array('mocoauto_api_end_point_version' => $MocoApiEpVer, 'products' => $products)))
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


    public function log_all_joinedAction()
    {
        $tablename1 = 'log_url';
        $tablename2 = 'log_url_info';
        $tablename3 = 'log_visitor';
        $tablename4 = 'log_visitor_info';
        $tablename5 = 'log_quote';
        $tablename6 = 'log_customer';



        if(!$this->_authorise()) {
            return $this;
        }

        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));

        $offset = $this->getRequest()->getParam('offset', 0);
        $page_size = $this->getRequest()->getParam('page_size', 20);
        $since = $this->getRequest()->getParam('since', 'ALL');
        $ipaddr = $this->getRequest()->getParam('ipaddr', 'ALL');
        $visitid = $this->getRequest()->getParam('visitid', 'ALL');

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
            elseif(!$_read ->showTableStatus(trim($tablename5,"'"))){
                $readresults=array($tablename5 ." table does not exist");
            }
            elseif(!$_read ->showTableStatus(trim($tablename6,"'"))){
                $readresults=array($tablename6 ." table does not exist");
            }
            else{
                $query = 'select ';
                $query = $query . $tablename1 . '.url_id, ' . $tablename1 . '.visitor_id, visit_time,';                 //log_url
                $query = $query . ' url, referer,';                                                                     //log_url_info
                $query = $query . ' session_id, first_visit_at, last_visit_at, '. $tablename3 . '.store_id,';           //log_visitor
                $query = $query . ' http_referer, http_user_agent, server_addr, remote_addr,';                          //log_visitor_info
                $query = $query . ' quote_id,';                                                                         //log_quote
                $query = $query . ' customer_id';                                                                       //log_customer
                $query = $query . ' from ' . $tablename1;
                $query = $query . ' Left join ' . $tablename2 . ' on ' . $tablename1 . '.url_id = ' . $tablename2 . '.url_id';
                $query = $query . ' Left join ' . $tablename3 . ' on ' . $tablename1 . '.visitor_id = ' . $tablename3 . '.visitor_id';
                $query = $query . ' Left join ' . $tablename4 . ' on ' . $tablename1 . '.visitor_id = ' . $tablename4 . '.visitor_id';
                $query = $query . ' Left join ' . $tablename5 . ' on ' . $tablename1 . '.visitor_id = ' . $tablename5 . '.visitor_id';
                $query = $query . ' Left join ' . $tablename6 . ' on ' . $tablename1 . '.visitor_id = ' . $tablename6 . '.visitor_id where url not like "%mocoauto%"';


                if($since != 'ALL'){
                    $query = $query . ' and visit_time > "' . $since . '"';
                }

                if($ipaddr != 'ALL'){
                     $query = $query . ' and remote_addr = "' . ip2long($ipaddr) . '"';
                }

                if($visitid != 'ALL'){
                     $query = $query . ' and ' . $tablename1 . '.visitor_id = "' . $visitid . '"';
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
        $ipaddr = $this->getRequest()->getParam('ipaddr', 'ALL');

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

                if($ipaddr != 'ALL'){
                     $query = $query . ' and remote_addr = "' . ip2long($ipaddr) . '"';
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

    public function sql_anytableAction()
    {

        if(!$this->_authorise()) {
            return $this;
        }

        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));

        $offset = $this->getRequest()->getParam('offset', 0);
        $page_size = $this->getRequest()->getParam('page_size', 100);
        $tablename1 = $this->getRequest()->getParam('tablename', 'no table name set');
        $since = $this->getRequest()->getParam('since', 'ALL');
        $created = $this->getRequest()->getParam('created', 'ALL');



        try{
            $_read = Mage::getSingleton('core/resource')->getConnection('core_read');
            if(!$_read ->showTableStatus(trim($tablename1,"'"))){
                $readresults=array($tablename1 ." table does not exist");
            }
            else{
                $query = 'select ';
                $query = $query . '*';                        
                $query = $query . ' from ' . $tablename1;
                if($since != 'ALL'){
                    $query = $query . ' where updated_at >= ' . "'" . $since . "'" . ' order by updated_at';
                }
//  Added a created_at select and order as some tables dont't have updated_at
                if($created != 'ALL'){
                    $query = $query . ' where created_at >= ' . "'" . $created . "'" . ' order by created_at';
                }

                $query = $query .' limit ' . $offset . ',' . $page_size;

                //Mage::log('DBG SQL: '. $query);

                if(($since != 'ALL') && ($created != 'ALL')){ // don't want to select and order on both 
                    $readresults[] = array('mocoauto_api_error' => 'since and created parameters are mutually exclusive');
                }
                else{
                    $readresults = $_read->fetchAll($query);
                }
            }
        }
        catch(Exception $e) {
                $readresults[] = array('mocoauto_api_error' => 'error reading ' . $tablename1 . ' : ' .  $e->getMessage());
        }

        $this->getResponse()
            ->setBody(json_encode($readresults))
            ->setHttpResponseCode(200)
            ->setHeader('Content-type', 'application/json', true);
        return $this;
    }

    public function ex_sql_anytableAction()
    {

        if(!$this->_authorise()) {
            return $this;
        }

        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));

        $offset = $this->getRequest()->getParam('offset', 0);
        $page_size = $this->getRequest()->getParam('page_size', 100);
        $tablename1 = $this->getRequest()->getParam('tablename', 'no table name set');

        try{
            $_read = Mage::getSingleton('core/resource')->getConnection('core_read');
            if(!$_read ->showTableStatus(trim($tablename1,"'"))){
                $readresults=array($tablename1 ." table does not exist");
            }
            else{
                $query = 'select ';
                $query = $query . '*';
                $query = $query . ' from ' . $tablename1;

                $query = $query .' limit ' . $offset . ',' . $page_size;

                //Mage::log('DBG SQL: '. $query);

                $readresults = $_read->fetchAll($query);
            }
        }
        catch(Exception $e) {
                $readresults[] = array('mocoauto_api_error' => 'error reading ' . $tablename1 . ' : ' .  $e->getMessage());
        }

        $this->getResponse()
            ->setBody(json_encode($readresults))
            ->setHttpResponseCode(200)
            ->setHeader('Content-type', 'application/json', true);
        return $this;
    }

    public function sql_describeAction()
    {

        if(!$this->_authorise()) {
            return $this;
        }

        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));
        $tablename1 = $this->getRequest()->getParam('tablename', 'no table name set');

        try{
            $_read = Mage::getSingleton('core/resource')->getConnection('core_read');
            if(!$_read ->showTableStatus(trim($tablename1,"'"))){
                $readresults=array($tablename1 ." table does not exist");
            }
            else{
                $query = 'describe ';
                $query = $query . $tablename1;

                //Mage::log('DBG SQL: '. $query);

                $readresults = $_read->fetchAll($query);
            }
        }
        catch(Exception $e) {
                $readresults[] = array('mocoauto_api_error' => 'error reading ' . $tablename1 . ' : ' .  $e->getMessage());
        }

        $this->getResponse()
            ->setBody(json_encode($readresults))
            ->setHttpResponseCode(200)
            ->setHeader('Content-type', 'application/json', true);
        return $this;
    }

    public function sql_showtablesAction()
    {

        if(!$this->_authorise()) {
            return $this;
        }

        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));

        try{
            $_read = Mage::getSingleton('core/resource')->getConnection('core_read');
            $query = 'show tables';

            //Mage::log('DBG SQL: '. $query);

            $readresults = $_read->fetchAll($query);
        }
        catch(Exception $e) {
                $readresults[] = array('mocoauto_api_error' => 'show tables error ' . ' : ' .  $e->getMessage());
        }

        $this->getResponse()
            ->setBody(json_encode($readresults))
            ->setHttpResponseCode(200)
            ->setHeader('Content-type', 'application/json', true);
        return $this;
    }

    public function old_sql_sales_flat_quoteAction()
    {
        $tablename1 = 'sales_flat_quote';


        if(!$this->_authorise()) {
            return $this;
        }

        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));

        $offset = $this->getRequest()->getParam('offset', 0);
        $page_size = $this->getRequest()->getParam('page_size', 100);
        $since = $this->getRequest()->getParam('since', 'ALL');
        $ipaddr = $this->getRequest()->getParam('ipaddr', 'ALL');

        try{
            $_read = Mage::getSingleton('core/resource')->getConnection('core_read');
            if(!$_read ->showTableStatus(trim($tablename1,"'"))){
                $readresults=array($tablename1 ." table does not exist");
            }
            else{
                $query = 'select ';
                $query = $query . 'entity_id, store_id, created_at, customer_email, remote_ip, reserved_order_id';
                $query = $query . ' from ' . $tablename1;
                $query = $query . ' where entity_id > 0';

                if($since != 'ALL'){
                    $query = $query . ' and created_at > "' . $since . '"';
                }

                if($ipaddr != 'ALL'){
                     $query = $query . ' and remote_ip = "' . $ipaddr . '"';
                }

                $query = $query .' limit ' . $offset . ',' . $page_size;

                //Mage::log('DBG SQL: '. $query);

                $readresults = $_read->fetchAll($query);
            }
        }
        catch(Exception $e) {
                $readresults[] = array('mocoauto_api_error' => 'error reading ' . $tablename1 . ' : ' .  $e->getMessage());
        }

        $this->getResponse()
            ->setBody(json_encode($readresults))
            ->setHttpResponseCode(200)
            ->setHeader('Content-type', 'application/json', true);
        return $this;
    }

    public function sql_sales_flat_quoteAction()
    {
        $tablename1 = 'sales_flat_quote';
        $tablename2 = 'sales_flat_quote_item';



        if(!$this->_authorise()) {
            return $this;
        }

        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));

        $offset = $this->getRequest()->getParam('offset', 0);
        $page_size = $this->getRequest()->getParam('page_size', 100);
        $since = $this->getRequest()->getParam('since', 'ALL');
        $ipaddr = $this->getRequest()->getParam('ipaddr', 'ALL');

        try{
            $_read = Mage::getSingleton('core/resource')->getConnection('core_read');
            if(!$_read ->showTableStatus(trim($tablename1,"'"))){
                $readresults=array($tablename1 ." table does not exist");
            }
            elseif(!$_read ->showTableStatus(trim($tablename2,"'"))){
                $readresults=array($tablename2 ." table does not exist");
            }
            else{
                $query = 'select ';
                $query = $query . $tablename1 . '.entity_id, ';                       // sales_flat_quote
                $query = $query . $tablename1 . '.store_id, ';                        // sales_flat_quote
                $query = $query . $tablename1 . '.created_at, ';                      // sales_flat_quote
                $query = $query . $tablename1 . '.updated_at, ';                      // sales_flat_quote
                $query = $query . $tablename1 . '.converted_at, ';                    // sales_flat_quote
                $query = $query . $tablename1 . '.customer_email, ';                  // sales_flat_quote
                $query = $query . $tablename1 . '.remote_ip, ';                       // sales_flat_quote
                $query = $query . $tablename1 . '.reserved_order_id, ';               // sales_flat_quote
                $query = $query . $tablename1 . '.is_active, ';                       // sales_flat_quote

                $query = $query . $tablename2 . '.item_id, ';                         // sales_flat_quote_item
                $query = $query . $tablename2 . '.quote_id, ';                        // sales_flat_quote_item
                $query = $query . $tablename2 . '.product_id, ';                      // sales_flat_quote_item
                $query = $query . $tablename2 . '.store_id, ';                        // sales_flat_quote_item
                $query = $query . $tablename2 . '.parent_item_id, ';                  // sales_flat_quote_item
                $query = $query . $tablename2 . '.product_type, ';                    // sales_flat_quote_item
                $query = $query . $tablename2 . '.qty,';                              // sales_flat_quote_item
                $query = $query . $tablename2 . '.price';                             // sales_flat_quote_item

                $query = $query . ' from ' . $tablename1;
                $query = $query . ' Left join ' . $tablename2 . ' on ' . $tablename1 . '.entity_id = ' . $tablename2 . '.quote_id';
                $query = $query . ' where ' . $tablename1 . '.entity_id > 0';

                if($since != 'ALL'){
                    $query = $query . ' and ' . $tablename1 . '.updated_at > "' . $since . '"';
                }

                if($ipaddr != 'ALL'){
                     $query = $query . ' and remote_ip = "' . $ipaddr . '"';
                }

                $query = $query .' limit ' . $offset . ',' . $page_size;

                // Mage::log('DBG SQL: '. $query);

                $readresults = $_read->fetchAll($query);
            }
        }
        catch(Exception $e) {
                $readresults[] = array('mocoauto_api_error' => 'error reading ' . $tablename1 . ' : ' .  $e->getMessage());
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

        Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID); //   set to admin view all sites and stores

        $_cartsCol = Mage::getResourceModel('sales/quote_collection')->addFieldToFilter('is_active', '1'); // 1 = quote has not been conveted to an order

        $magentoVersion = Mage::getVersion();
        if (version_compare($magentoVersion, '1.7', '>=')){
            $aboveVersion17Flag = 1; 
        }
        else {
            $aboveVersion17Flag = 0;
        }

        if($aboveVersion17Flag){                                          // This will only work with Magento > 1.6
            $_cartsCol->addFieldToFilter(                                 // If there is no email or customer id we dont want the cart.
                        array(
                            'customer_id',                                //attribute_1 with key 0
                            'customer_email',                             //attribute_2 with key 1
                        ),
                        array(
                              array('gteq'=> 1),                        // This form creates a NOT NULL query. 
                              array('notnull'=>1),
                        )
                    );
        }

        if($since != 'ALL'){
            $_cartsCol->addFieldToFilter('updated_at', array('gteq' =>$since)); // If date filter supplied include empty carts
        }
        else{
            $_cartsCol->addFieldToFilter('items_count', array('neq' => 0));     // If no date filter supplied (ALL) only include carts with items
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

//  1.5.2.1 first version with this action

    public function invoicesAction()
    {
        if(!$this->_authorise()) {
            return $this;
        }

        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));

        $offset = $this->getRequest()->getParam('offset', 0);
        $page_size = $this->getRequest()->getParam('page_size', 20);
        $since = $this->getRequest()->getParam('since','ALL');
        $gTE = $this->getRequest()->getParam('gte', 'ALL');

        $_invoiceCol = Mage::getModel('sales/order_invoice')->getCollection()->addAttributeToSelect('*');


        if($since != 'ALL'){
            $_invoiceCol->addAttributeToFilter('updated_at', array('gteq' =>$since));
        }

        if($gTE != 'ALL'){
            $_invoiceCol->addFieldToFilter('entity_id', array('gteq' =>$gTE));
            $_invoiceCol->getSelect()->limit($page_size, ($offset * $page_size))->order('entity_id');
        }
        else{
            $_invoiceCol->getSelect()->limit($page_size, ($offset * $page_size))->order('updated_at');
        }

        $invoices = array();

        foreach($_invoiceCol as $_invoice) {

            $invoice = array();

            try{
                $invoice['moco_start_of_invoice_record'] = 'True';
                $invoicedetails = array();
                $invoicedetails = $_invoice->toArray();

                foreach ($invoicedetails as $key => $value) {
                    $invoice[$key] = $value;
                }


                $_invoiceItemsCol = $_invoice->getItemsCollection();
                $invoiceitems = array();

                foreach($_invoiceItemsCol as $_invoiceitem){
                    $invoiceitems[] = $_invoiceitem->toArray();
                }
                $invoice['moco_tls'] = $invoiceitems;


                $order['moco_end_of_invoice_record'] = 'True';
            }
            catch (Exception $e) {
                $order['mocoauto_api_error'] = 'invoice record: ' . $e->getMessage();
            }
            $invoices[] = $invoice;

        }

        $this->getResponse()
            ->setBody(json_encode($invoices))
            ->setHttpResponseCode(200)
            ->setHeader('Content-type', 'application/json', true);
        return $this;
    }

//  1.5.2.1 first verstion with this action

    public function invoice_idsAction()
    {
        if(!$this->_authorise()) {
            return $this;
        }

        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));

        $offset = $this->getRequest()->getParam('offset', 0);
        $page_size = $this->getRequest()->getParam('page_size', 20);

        $invoiceIds = Mage::getModel('sales/order_invoice')->getCollection()->getAllIds($limit= $page_size, $offset =($offset * $page_size));

        $this->getResponse()
            ->setBody(json_encode($invoiceIds))
            ->setHttpResponseCode(200)
            ->setHeader('Content-type', 'application/json', true);
        return $this;
    }

    public function creditsAction() 
    {
        if(!$this->_authorise()) {
            return $this;
        }

        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));

        $offset = $this->getRequest()->getParam('offset', 0);
        $page_size = $this->getRequest()->getParam('page_size', 20);
        $since = $this->getRequest()->getParam('since','ALL');
        $gTE = $this->getRequest()->getParam('gte', 'ALL');

        $_creditCol = Mage::getModel('sales/order_creditmemo')->getCollection()->addAttributeToSelect('*');


        if($since != 'ALL'){    
            $_creditCol->addAttributeToFilter('updated_at', array('gteq' =>$since));
        }

        if($gTE != 'ALL'){
            $_creditCol->addFieldToFilter('entity_id', array('gteq' =>$gTE));
            $_creditCol->getSelect()->limit($page_size, ($offset * $page_size))->order('entity_id');
        }
        else{
            $_creditCol->getSelect()->limit($page_size, ($offset * $page_size))->order('updated_at');
        }


        $credits = array();

        foreach($_creditCol as $_credit) {
 
            $credit = array();

            try{
                $credit['moco_start_of_credit_record'] = 'True';
                $creditdetails = array();
                $creditdetails = $_credit->toArray();

                foreach ($creditdetails as $key => $value) {
                    $credit[$key] = $value;
                }


                $_creditItemsCol = $_credit->getItemsCollection();
                $credititems = array();

                foreach($_creditItemsCol as $_credititem){
                    $credititems[] = $_credititem->toArray();
                }
                $credit['moco_tls'] = $credititems;


                $order['moco_end_of_credit_record'] = 'True';
            }
            catch (Exception $e) {
                $order['mocoauto_api_error'] = 'credit record: ' . $e->getMessage();
            }
            $credits[] = $credit;

        }

        $this->getResponse()
            ->setBody(json_encode($credits))
            ->setHttpResponseCode(200)
            ->setHeader('Content-type', 'application/json', true);
        return $this;
    }



    public function credit_idsAction()
    {
        if(!$this->_authorise()) {
            return $this;
        }

        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));

        $offset = $this->getRequest()->getParam('offset', 0);
        $page_size = $this->getRequest()->getParam('page_size', 20);

        $creditIds = Mage::getModel('sales/order_creditmemo')->getCollection()->getAllIds($limit= $page_size, $offset =($offset * $page_size));

        $this->getResponse()
            ->setBody(json_encode($creditIds))
            ->setHttpResponseCode(200)
            ->setHeader('Content-type', 'application/json', true);
        return $this;
    }


    public function list_modulesAction()
    {

        if(!$this->_authorise()) {
            return $this;
        }

        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));

        try{
            $modules = Mage::getConfig()->getNode('modules')->children();
        }
        catch(Exception $e) {
                $readresults[] = array('mocoauto_api_error' => 'list installed modules error ' . ' : ' .  $e->getMessage());
        }

        $this->getResponse()
            ->setBody(json_encode($modules))
            ->setHttpResponseCode(200)
            ->setHeader('Content-type', 'application/json', true);
        return $this;
    }

    public function giftcardsAction()
    {
        $MocoApiEpVer = '1.0.0';  // First version with version returned. 
        $tablename1 = 'enterprise_giftcardaccount_history';
        $tablename2 = 'enterprise_giftcardaccount';

        if(!$this->_authorise()) {
            return $this;
        }

        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));

        $offset = $this->getRequest()->getParam('offset', 0);
        $page_size = $this->getRequest()->getParam('page_size', 20);
        $since = $this->getRequest()->getParam('since', 'ALL');

        $readresults[] = array('mocoauto_api_end_point_version' => $MocoApiEpVer);

        try{
            $_read = Mage::getSingleton('core/resource')->getConnection('core_read');
            if(!$_read ->showTableStatus(trim($tablename1,"'"))){
                $readresults[]=array($tablename1 ." table does not exist");
            }
            elseif(!$_read ->showTableStatus(trim($tablename2,"'"))){
                $readresults[]=array($tablename2 ." table does not exist");
            }
            else{
                $query = 'select ';
                $query = $query . $tablename1 . '.*, ';                 
                $query = $query . $tablename2 . '.* ';
                $query = $query . ' from ' . $tablename1;
                $query = $query . ' Left join ' . $tablename2 . ' on ' . $tablename1 . '.giftcardaccount_id = ' . $tablename2 . '.giftcardaccount_id';


                if($since != 'ALL'){
                    $query = $query . ' where updated_at > "' . $since . '"';
                }

                $query = $query .' order by updated_at limit ' . $offset . ',' . $page_size;

                //Mage::log('DBG SQL: '. $query);

                $readresults[] = $_read->fetchAll($query);
            }
        }
        catch(Exception $e) {
                $readresults[] = array('mocoauto_api_error' => 'giftcard sql error: ' . $e->getMessage());
        }

        $this->getResponse()
            ->setBody(json_encode($readresults))
            ->setHttpResponseCode(200)
            ->setHeader('Content-type', 'application/json', true);
        return $this;
    }

}
