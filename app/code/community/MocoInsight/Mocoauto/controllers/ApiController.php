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
//  log_visitorAction
//  log_visitor_infoAction
//  log_customerAction
//  subscribersAction
//  storesAction
//  unconvertedcartsAction
//  wishlistsAction
//  installinfoAction
//
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
        'log_url' => $log_urlcount,
        'log_url_info' => $log_url_infocount,
        'log_visitor' => $log_visitorcount,
        'log_visitor_info' => $log_visitor_infocount,
        'log_customer' => $log_customercount,
        'System Date Time' => $currentSystemTime,
        'Magento Version' => $magentoVersion,
        'MocoAPI Version' => $apiversion,
        'PHP Version' => $phpversion
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

// Grab an array of tax rates for lookup later


        $store = Mage::app()->getStore('default');
        $request = Mage::getSingleton('tax/calculation')->getRateRequest(null, null, null, $store);

        $products = array();
        $products[] = array('success' => 'true');        
        foreach($_productCol as $_product){

// get all the attributes of the product
            $attributes = $_product->getAttributes();
        
            foreach ($attributes as $attribute) {      
                $attributeCode = $attribute->getAttributeCode();        
                try {
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
                catch (Exception $e) {
                    $products[] = array($attributeCode => 'Mocoauto_error: ' . $e->getMessage());
                }
            }   
        

// get the tax rate of the product

            $taxclassid = $_product->getData('tax_class_id');
            if(isset($taxClasses["value_".$taxclassid])){
                $taxpercent = $taxClasses["value_".$taxclassid];
            } 
            else {
                $taxpercent = 'not defined';
            }

            $taxpercent = Mage::getSingleton('tax/calculation')->getRate($request->setProductClassId($taxclassid));
            $products[] = array('moco_TaxRate:' => $taxpercent);

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
            $query = 'select * from ' . $tablename . ' limit ' . $offset . ',' . $page_size;
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

    public function unconvertedcartsAction()
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
        

        $this->getResponse()
            ->setBody(json_encode($installinfo))
            ->setHttpResponseCode(200)
            ->setHeader('Content-type', 'application/json', true);
        return $this;
    }
}
