<?php
//  Version 1.2.7
//
//  Make sure you update version in /var/www/html/app/code/community/MocoInsight/Mocoauto/etc/config.xml
//
//  DEBUG example  "Mage::log('DBG Count: '.$customercount);"
//
//  Provides the following actions via a RestAPI
//
//  statsAction     
//  ordersAction
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
//


class MocoInsight_Mocoauto_ApiController extends Mage_Core_Controller_Front_Action
{

    public function _authorise()
    {

        $apiversion = (String)Mage::getConfig()->getNode()->modules->MocoInsight_Mocoauto->version;

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
                    ->setBody(json_encode(array('success' => false, 'message' => 'API access disabled', 'MocoAPI version' => $apiversion)))
                    ->setHttpResponseCode(403)
                    ->setHeader('Content-type', 'application/json', true);
                return false;
        }

        // Check the token passed in the header
        if(!$token || $token != $apiToken) {
                $this->getResponse()
                    ->setBody(json_encode(array('success' => false, 'message' => 'Not authorised','MocoAPI version' => $apiversion)))
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

    public function statsAction()   // Return the number of Product, Orders and Customers with optional since filter
    {
        if(!$this->_authorise()) {
            return $this;
        }

        $time_start = microtime(true); 

        $currentSystemTime = date('Y-m-d H:i:s', time());
        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));
        $since = $this->getRequest()->getParam('since','All');

        $_productCol = Mage::getModel('catalog/product')->getCollection();
        if($since != 'All'){    
           $_productCol->addAttributeToFilter('updated_at', array('gteq' =>$since));
        }
        $productcount = $_productCol->getSize();
            
        $_orderCol = Mage::getModel('sales/order')->getCollection();
        if($since != 'All'){    
           $_orderCol->addAttributeToFilter('updated_at', array('gteq' =>$since));
        }
        $ordercount = $_orderCol->getSize();
 
        $_customerCol = Mage::getModel('customer/customer')->getCollection();
        if($since != 'All'){    
           $_customerCol->addAttributeToFilter('updated_at', array('gteq' =>$since));
        }
        $customercount = $_customerCol->getSize();


        $_categoryCol = Mage::getModel('catalog/category')->getCollection();
        if($since != 'All'){    
           $_categoryCol->addAttributeToFilter('updated_at', array('gteq' =>$since));
        }
        $categorycount = $_categoryCol->getSize();

        $_wishlistCol = Mage::getModel('wishlist/wishlist')-> getCollection();
        if($since != 'All'){
           $_wishlistCol->addFieldToFilter('updated_at', array('gteq' =>$since));
        }
        $wishlistcount = $_wishlistCol->getSize();

        $_cartsCol = Mage::getResourceModel('sales/quote_collection')->addFieldToFilter('is_active', '1');
        if($since != 'All'){
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

//
//  Check size of log files
//  1. Check if isTableExists method is defined (It appears Magento v1.5.0.1 defines it differently)
//  2. Then check if each table exists
//  3. Then get size of the 5 tables.


        $_read = Mage::getSingleton('core/resource')->getConnection('core_read');

        if (method_exists($_read, 'isTableExists')){

            $tablename = 'log_url';         // Set the table name here

            if(!$_read ->isTableExists($tablename)){        //Table does not exist
                $logurlcount = "table does not exist";
            }
            else{
                $query = 'select count(*) AS id from ' . $tablename;
                $log_urlcount = $_read->fetchOne($query);
            }

            $tablename = 'log_url_info';         // Set the table name here

            if(!$_read ->isTableExists($tablename)){        //Table does not exist
                $log_url_infocount = "table does not exist";
            }
            else{
                $query = 'select count(*) AS id from ' . $tablename;
                $log_url_infocount = $_read->fetchOne($query);
            }

            $tablename = 'log_visitor';         // Set the table name here

            if(!$_read ->isTableExists($tablename)){        //Table does not exist
                $log_visitorcount = "table does not exist";
            }
            else{
                $query = 'select count(*) AS id from ' . $tablename;
                $log_visitorcount = $_read->fetchOne($query);
            }

            $tablename = 'log_visitor_info';         // Set the table name here

            if(!$_read ->isTableExists($tablename)){        //Table does not exist
                $log_visitor_infocount = "table does not exist";
            }
            else{
                $query = 'select count(*) AS id from ' . $tablename;
                $log_visitor_infocount = $_read->fetchOne($query);
            }

            $tablename = 'log_customer';         // Set the table name here

            if(!$_read ->isTableExists($tablename)){        //Table does not exist
                $log_countcount = "table does not exist";
            }   
            else{
                $query = 'select count(*) AS id from ' . $tablename;
                $log_customercount = $_read->fetchOne($query);
            }

        }
        else {
            $log_urlcount = "isTableExists is an undefined method";
            $log_url_infocount = "isTableExists is an undefined method";
            $log_visitorcount = "isTableExists is an undefined method";
            $log_visitor_infocount = "isTableExists is an undefined method";
            $log_customercount = "isTableExists is an undefined method";

        }



    $magentoVersion = Mage::getVersion();
    $apiversion = (String)Mage::getConfig()->getNode()->modules->MocoInsight_Mocoauto->version;
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
        'log_url' => $log_urlcount,
        'log_url_info' => $log_url_infocount,
        'log_visitor' => $log_visitorcount,
        'log_visitor_info' => $log_visitor_infocount,
        'log_customer' => $log_customercount,
        'System Date Time' => $currentSystemTime,
        'Magento Version' => $magentoVersion,
        'MocoAPI Version' => $apiversion,
        'PHP Version' => $phpversion,
        'API processing time' => (microtime(true) - $time_start)
         );
     
        $this->getResponse()
            ->setBody(json_encode($stats))
            ->setHttpResponseCode(200)
            ->setHeader('Content-type', 'application/json', true);
        return $this;
    }

    public function exstatsAction()
    {
        if(!$this->_authorise()) {
            return $this;
        }

        $currentSystemTime = date('Y-m-d H:i:s', time());
        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));
        $since = $this->getRequest()->getParam('since','All');

        $_productCol = Mage::getModel('catalog/product')->getCollection();
        if($since != 'All'){    
           $_productCol->addAttributeToFilter('updated_at', array('gteq' =>$since));
        }
        $productcount = $_productCol->getSize();
            
        $_orderCol = Mage::getModel('sales/order')->getCollection();
        if($since != 'All'){    
           $_orderCol->addAttributeToFilter('updated_at', array('gteq' =>$since));
        }
        $ordercount = $_orderCol->getSize();
 
        $_customerCol = Mage::getModel('customer/customer')->getCollection();
        if($since != 'All'){    
           $_customerCol->addAttributeToFilter('updated_at', array('gteq' =>$since));
        }
        $customercount = $_customerCol->getSize();


        $_categoryCol = Mage::getModel('catalog/category')->getCollection();
        if($since != 'All'){    
           $_categoryCol->addAttributeToFilter('updated_at', array('gteq' =>$since));
        }
        $categorycount = $_categoryCol->getSize();

        $_wishlistCol = Mage::getModel('wishlist/wishlist')-> getCollection();
        if($since != 'All'){
           $_wishlistCol->addFieldToFilter('updated_at', array('gteq' =>$since));
        }
        $wishlistcount = $_wishlistCol->getSize();

        $_cartsCol = Mage::getResourceModel('sales/quote_collection')->addFieldToFilter('is_active', '1');

        if($since != 'All'){
            $_cartsCol->addFieldToFilter('updated_at', array('gteq' =>$since));
        }
        $cartscount = $_cartsCol->getSize();

        $_subscriberCol = Mage::getModel('newsletter/subscriber')-> getCollection();

        $subscribercount = $_subscriberCol->getSize();
//
//  Check size of log files
//  1. Check if isTableExists method is defined (It appears Magento v1.5.0.1 defines it differently)
//  2. Then check if each table exists
//  3. Then get size of the 5 tables.


        $_read = Mage::getSingleton('core/resource')->getConnection('core_read');

        if (method_exists($_read, 'isTableExists')){

            $tablename = 'log_url';         // Set the table name here

            if(!$_read ->isTableExists($tablename)){        //Table does not exist
                $logurlcount = "table does not exist";
            }
            else{
                $query = 'select count(*) AS id from ' . $tablename;
                $log_urlcount = $_read->fetchOne($query);
            }

            $tablename = 'log_url_info';         // Set the table name here

            if(!$_read ->isTableExists($tablename)){        //Table does not exist
                $log_url_infocount = "table does not exist";
            }
            else{
                $query = 'select count(*) AS id from ' . $tablename;
                $log_url_infocount = $_read->fetchOne($query);
            }

            $tablename = 'log_visitor';         // Set the table name here

            if(!$_read ->isTableExists($tablename)){        //Table does not exist
                $log_visitorcount = "table does not exist";
            }
            else{
                $query = 'select count(*) AS id from ' . $tablename;
                $log_visitorcount = $_read->fetchOne($query);
            }

            $tablename = 'log_visitor_info';         // Set the table name here

            if(!$_read ->isTableExists($tablename)){        //Table does not exist
                $log_visitor_infocount = "table does not exist";
            }
            else{
                $query = 'select count(*) AS id from ' . $tablename;
                $log_visitor_infocount = $_read->fetchOne($query);
            }

            $tablename = 'log_customer';         // Set the table name here

            if(!$_read ->isTableExists($tablename)){        //Table does not exist
                $log_countcount = "table does not exist";
            }   
            else{
                $query = 'select count(*) AS id from ' . $tablename;
                $log_customercount = $_read->fetchOne($query);
            }

        }
        else {
            $log_urlcount = "isTableExists is an undefined method";
            $log_url_infocount = "isTableExists is an undefined method";
            $log_visitorcount = "isTableExists is an undefined method";
            $log_visitor_infocount = "isTableExists is an undefined method";
            $log_customercount = "isTableExists is an undefined method";

        }

    $magentoVersion = Mage::getVersion();
    $apiversion = (String)Mage::getConfig()->getNode()->modules->MocoInsight_Mocoauto->version;

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
        'log_url' => $log_urlcount,
        'log_url_info' => $log_url_infocount,
        'log_visitor' => $log_visitorcount,
        'log_visitor_info' => $log_visitor_infocount,
        'log_customer' => $log_customercount,
        'System Date Time' => $currentSystemTime,
        'Magento Version' => $magentoVersion,
        'MocoAPI Version' => $apiversion
         );
     
        $this->getResponse()
            ->setBody(json_encode($stats))
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
        $since = $this->getRequest()->getParam('since','All');

        $_orderCol = Mage::getModel('sales/order')->getCollection()->addAttributeToSelect('*');
        $_orderCol->getSelect()->limit($page_size, ($offset * $page_size))->order('updated_at');

        if($since != 'All'){    
            $_orderCol->addAttributeToFilter('updated_at', array('gteq' =>$since));
        }

        $orders = array();

        foreach($_orderCol as $_order) {

            try{
                $orders[] = array('moco_start_of_order_record' => 'True');
                $orders[] = $_order->toArray();

                if(is_object($_order->getBillingAddress())){

                    $_billing_address = $_order->getBillingAddress();
                    $orders[] = array('moco_start_of_address' => 'True');
                    $orders[] = $_billing_address->toArray();
                    $orders[] = array('moco_end_of_address' => 'True');
                }

                try{
                    $_payment_info = $_order->getPayment();
                    $orders[] = array('moco_start_of_paymentinfo' => 'True');
                    $paymentinfo[] = $_payment_info->toArray();
		    foreach($paymentinfo as $key => $value){
                        unset($paymentinfo[$key]['cc_number_enc']);
                        unset($paymentinfo[$key]['cc_last4']);
                        unset($paymentinfo[$key]['cc_exp_month']);
                        unset($paymentinfo[$key]['cc_exp_year']);
                    }
                    $orders[] = array($paymentinfo);

                    $orders[] = array('moco_end_of_paymentinfo' => 'True');
                }
                catch (Exception $e) {
                    $orders[] = array('billing info' => 'Mocoauto_error: ' . $e->getMessage());
                } 
                $_orderItemsCol = $_order->getItemsCollection();

                foreach($_orderItemsCol as $_orderitem){
                    $orders[] = $_orderitem->toArray();
                } 
                $orders[] = array('moco_end_of_order_record' => 'True');
            }
            catch (Exception $e) {
                $orders[] = array('order record' => 'Mocoauto_error: ' . $e->getMessage());
            }
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
        $since = $this->getRequest()->getParam('since','All');

        $_orderCol = Mage::getModel('sales/order')->getCollection()->addAttributeToSelect('*');
        $_orderCol->getSelect()->limit($page_size, ($offset * $page_size))->order('updated_at');

        if($since != 'All'){
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


    public function customersAction()
    {
        if(!$this->_authorise()) {
            return $this;
        }

        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));

        $offset = $this->getRequest()->getParam('offset', 0);
        $page_size = $this->getRequest()->getParam('page_size', 20);
        $since = $this->getRequest()->getParam('since', 'All');

        $_customerCol = Mage::getModel('customer/customer')->getCollection()->addAttributeToSelect('*');
        $_customerCol->getSelect()->limit($page_size, ($offset * $page_size))->order('updated_at');

        if($since != 'All'){    
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
        $since = $this->getRequest()->getParam('since', 'All');

        $_categoryCol = Mage::getModel('catalog/category')->getCollection()->addAttributeToSelect('*');
        $_categoryCol->getSelect()->limit($page_size, ($offset * $page_size))->order('updated_at');
        
        if($since != 'All'){    
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

    public function productsAction()
    {
        if(!$this->_authorise()) {
            return $this;
        }

        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));

        $offset = $this->getRequest()->getParam('offset', 0);
        $page_size = $this->getRequest()->getParam('page_size', 20);
        $since = $this->getRequest()->getParam('since', 'All');

        $_productCol = Mage::getModel('catalog/product')->getCollection()->addAttributeToSelect('*');
        $_productCol->getSelect()->limit($page_size, ($offset * $page_size))->order('updated_at');

        if($since != 'All'){    
           $_productCol->addAttributeToFilter('updated_at', array('gteq' =>$since));
        }


        $products[] = array('success' => 'true');        

        foreach($_productCol as $_product){

// get all the attributes of the product
            $attributes = $_product->getAttributes();
        
            foreach ($attributes as $attribute) {      
                $attributeCode = $attribute->getAttributeCode();        
                try {
                    $value = $attribute->getFrontend()->getValue($_product);

                    switch ($attributeCode){
                        case 'in_depth':
			    break;
                        case 'description':
                            break;
                        case 'short_description':
                            break;
                        default:
                            $products[] = array($attributeCode => $value);
                            break;
                    }
                }
                catch (Exception $e) {
                    $products[] = array($attributeCode => 'Mocoauto_error: ' . $e->getMessage());
                }
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
                $products[] = array('moco_product_inventory' => 'Mocoauto_error: ' . $e->getMessage());
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

    public function exproductsAction() // previous version of API 
    {
        if(!$this->_authorise()) {
            return $this;
        }

        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));

        $offset = $this->getRequest()->getParam('offset', 0);
        $page_size = $this->getRequest()->getParam('page_size', 20);
        $since = $this->getRequest()->getParam('since', 'All');

        $_productCol = Mage::getModel('catalog/product')->getCollection()->addAttributeToSelect('*');
        $_productCol->getSelect()->limit($page_size, ($offset * $page_size))->order('updated_at');

        if($since != 'All'){    
           $_productCol->addAttributeToFilter('updated_at', array('gteq' =>$since));
        }

        $products = array();
        $products[] = array('success' => 'true');        
        foreach($_productCol as $_product){

// get all the attributes of the product
            $attributes = $_product->getAttributes();
        
            foreach ($attributes as $attribute) {      
                $attributeCode = $attribute->getAttributeCode();        
                $value = $attribute->getFrontend()->getValue($_product);

                switch ($attributeCode){
                    case 'description':
                        break;
                    case 'short_description':
                        break;
                    default:
                        $products[] = array($attributeCode => $value);
                        break;
                }
            }   
        

// get all the categories of the product

            $categories = $_product->getCategoryCollection()->addAttributeToSelect('name');
        
            foreach ($categories as $category) {      
                $products[] = array('moco_category' => $category->getID());
            }

// if type is configurable get simple product children

            if($_product->getTypeID() == 'configurable'){
                $assocProductIDs = Mage::getModel('catalog/product_type_configurable')->getUsedProducts(null,$_product);

                foreach($assocProductIDs as $assocProduct){
                    $products[] = array('childProductID' => $assocProduct->getID());
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
        $since = $this->getRequest()->getParam('since', 'All');

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
        $since = $this->getRequest()->getParam('since', 'All');

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
        $since = $this->getRequest()->getParam('since', 'All');

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

            if($since != 'All'){
                $query = $query . ' and visit_time > "' . $since . '"';
            }

            $query = $query .' limit ' . $offset . ',' . $page_size;

            $readresults = $_read->fetchAll($query);
        }
    }

    public function log_all_joinedAction()
    {
        $tablename1 = 'log_url';         // Set the table name here
        $tablename2 = 'log_url_info';         // Set the table name here
        $tablename3 = 'log_visitor';         // Set the table name here
        $tablename4 = 'log_visitor_info';         // Set the table name here


        if(!$this->_authorise()) {
            return $this;
        }

        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));

        $offset = $this->getRequest()->getParam('offset', 0);
        $page_size = $this->getRequest()->getParam('page_size', 20);
        $since = $this->getRequest()->getParam('since', 'All');

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

            if($since != 'All'){
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
        $since = $this->getRequest()->getParam('since', 'All');

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
        $since = $this->getRequest()->getParam('since', 'All');

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
        $since = $this->getRequest()->getParam('since', 'All');

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

            if($since != 'All'){
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
        $since = $this->getRequest()->getParam('since', 'All');

        $_read = Mage::getSingleton('core/resource')->getConnection('core_read');

        if(!$_read ->isTableExists($tablename)){    //Table does not exist
            $readresults=array($tablename ." table does not exist"); 
        }           
        else{
            $query = 'select * from ' . $tablename;

            if($since != 'All'){
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

    public function subscribersAction()
    {
        if(!$this->_authorise()) {
            return $this;
        }

        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));

        $offset = $this->getRequest()->getParam('offset', 0);
        $page_size = $this->getRequest()->getParam('page_size', 20);
        $since = $this->getRequest()->getParam('since', 'All');

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
              $storeInfo['tax/calculation/price_includes_tax'] = Mage::getStoreConfig('tax/calculation/price_includes_tax', $_store->getStoreId());
              $storeInfo['tax/defaults/country'] = Mage::getStoreConfig('tax/defaults/country', $_store->getStoreId());
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

    public function unconvertedcartsAction()//This query returns only no empty carts when no dat filter applied
    {
        if(!$this->_authorise()) {
            return $this;
        }

        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));

        $offset = $this->getRequest()->getParam('offset', 0);
        $page_size = $this->getRequest()->getParam('page_size', 20);
        $since = $this->getRequest()->getParam('since', 'All');

        $_cartsCol = Mage::getResourceModel('sales/quote_collection')->addFieldToFilter('is_active', '1');
        $_cartsCol->getSelect()->limit($page_size, ($offset * $page_size))->order('updated_at');

        if($since != 'All'){
            $_cartsCol->addFieldToFilter('updated_at', array('gteq' =>$since));
        }
        else{
           $_cartsCol->addFieldToFilter('items_count', array('neq' => 0));
        } 
  
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
                    $carts[] = array('moco_unable_to_read_cart' => 'Mocoauto_error: ' . $e->getMessage());
            }
        }

        $this->getResponse()
            ->setBody(json_encode($carts))
            ->setHttpResponseCode(200)
            ->setHeader('Content-type', 'application/json', true);
        return $this;
    }
    public function exunconvertedcartsAction()
    {
        if(!$this->_authorise()) {
            return $this;
        }

        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));

        $offset = $this->getRequest()->getParam('offset', 0);
        $page_size = $this->getRequest()->getParam('page_size', 20);
        $since = $this->getRequest()->getParam('since', 'All');

        $_cartsCol = Mage::getResourceModel('sales/quote_collection')->addFieldToFilter('is_active', '1');
        $_cartsCol->getSelect()->limit($page_size, ($offset * $page_size))->order('updated_at');

        if($since != 'All'){
           $_cartsCol->addFieldToFilter('updated_at', array('gteq' =>$since));
        }

        $carts = array();

        foreach($_cartsCol as $_cart) {
            $carts[] = array('moco_start_of_cart_record' => 'True');
            $carts[] = $_cart->toArray();
            $_cartItemsCol = $_cart -> getItemsCollection();
    
            foreach($_cartItemsCol as $_cartitem){
                //$carts[] = $_cartitem->toArray();
                $carts[] = array('product_id'  => $_cartitem->getProductId());
                $carts[] = array('product_qty' => $_cartitem->getQty());
                $carts[] = array('updated_at'  => $_cartitem->getUpdatedAt());
            }
            $carts[] = array('moco_end_of_cart_record' => 'True');
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
        $since = $this->getRequest()->getParam('since', 'All');

        $_wishlistCol = Mage::getModel('wishlist/wishlist')-> getCollection();
        $_wishlistCol->getSelect()->limit($page_size, ($offset * $page_size))->order('updated_at');

        if($since != 'All'){
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
        $since = $this->getRequest()->getParam('since', 'All');

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
