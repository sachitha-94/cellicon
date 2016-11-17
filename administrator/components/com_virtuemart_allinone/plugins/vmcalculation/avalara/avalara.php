<?php

if (!defined('_JEXEC'))
die('Direct Access to ' . basename(__FILE__) . ' is not allowed.');

/**
 * Calculation plugin for quantity based price rules
 *
 * @version $Id:$
 * @package VirtueMart
 * @subpackage Plugins - avalara
 * @author Max Milbers
 * @copyright Copyright (C) 2012 iStraxx - All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 *
 *
 */

if (!class_exists('vmCalculationPlugin')) require(JPATH_VM_PLUGINS.DS.'vmcalculationplugin.php');


class plgVmCalculationAvalara extends vmCalculationPlugin {

	// instance of class
	// 	public static $_this = false;

	var $_dev = TRUE;

	function __construct(& $subject, $config) {
		// 		if(self::$_this) return self::$_this;
		parent::__construct($subject, $config);

		$varsToPush = array(
			'activated'          => array(0, 'int'),
			'company_code'       => array('', 'char'),
			'account'       => array('', 'char'),
			'license'     => array('', 'char'),
		);

		$this->setConfigParameterable ('calc_params', $varsToPush);

		$this->_loggable = TRUE;
		$this->tableFields = array('id', 'virtuemart_order_id', 'client_ip', 'sentValue','recievedValue');
		$this->_tableId = 'id';
		$this->_tablepkey = 'id';

		if (JVM_VERSION === 2) {
			define ('VMAVALARA_PATH', JPATH_ROOT . DS . 'plugins' . DS . 'vmcalculation' . DS . 'avalara' );
		} else {
			define ('VMAVALARA_PATH', JPATH_ROOT . DS . 'plugins' . DS . 'vmcalculation' );
		}
		define('VMAVALARA_CLASS_PATH', VMAVALARA_PATH . DS . 'classes' );

		require(VMAVALARA_PATH.DS.'AvaTax.php');	// include in all Avalara Scripts

		if(!class_exists('ATConfig')) require (VMAVALARA_CLASS_PATH.DS.'ATConfig.class.php');

// 		$this->tableFields = array('id','virtuemart_calc_id','calc_affected','calc_amount_cond','calc_amount_cond_min','calc_amount_cond_max','calc_amount_dimunit','calc_quantity_relvalue');

// 		$this->_tableId = 'id';
		// 		self::$_this = $this;
	}


	function  plgVmOnStoreInstallPluginTable($jplugin_name) {
//return $this->onStoreInstallPluginTable('calculation');
	}


	/**
	 * Gets the sql for creation of the table
	 * @author Max Milbers
	 */
	public function getVmPluginCreateTableSQL() {

 		return "CREATE TABLE IF NOT EXISTS `" . $this->_tablename . "` (
 			    `id` mediumint(1) unsigned NOT NULL AUTO_INCREMENT ,
 			    `virtuemart_calc_id` mediumint(1) UNSIGNED DEFAULT NULL,
 			    `activated` int(1),
 			    `account` char(255),
 			    `license` char(255),
 			    `created_on` datetime NOT NULL default '0000-00-00 00:00:00',
 			    `created_by` int(11) NOT NULL DEFAULT 0,
 			    `modified_on` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
 			    `modified_by` int(11) NOT NULL DEFAULT 0,
 			    `locked_on` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
 			    `locked_by` int(11) NOT NULL DEFAULT 0,
 			     PRIMARY KEY (`id`),
 			     KEY `idx_virtuemart_calc_id` (`virtuemart_calc_id`)
 			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='Table for avalara' AUTO_INCREMENT=1 ;";

	}


	function plgVmAddMathOp(&$entryPoints){
 		$entryPoints[] = array('calc_value_mathop' => 'avalara', 'calc_value_mathop_name' => 'Avalara');
	}

	function plgVmOnDisplayEdit(&$calc,&$html){

	//	if (!class_exists ('VmTable')) {
		//	require(JPATH_VM_ADMINISTRATOR . DS . 'helpers' . DS . 'vmtable.php');
	//	}
		//VmTable::bindParameterable ($calc, $this->_xParams, $this->_varsToPushParam);

		//$html .= '<fieldset><legend>'.JText::_('VMCALCULATION_AVALARA').'</legend>';
		$html .= '<table>';

		$html .= VmHTML::row('checkbox','VMCALCULATION_AVALARA_ACTIVATED','activated',$calc->activated);
		$html .= VmHTML::row('input','VMCALCULATION_AVALARA_COMPANY_CODE','company_code',$calc->company_code);
		$html .= VmHTML::row('input','VMCALCULATION_AVALARA_ACCOUNT','account',$calc->account);
		$html .= VmHTML::row('input','VMCALCULATION_AVALARA_LICENSE','license',$calc->license);
	//	$html .= VmHTML::row('checkbox','VMCALCULATION_ISTRAXX_AVALARA_TRACE','trace',$calc->trace);

		$html .= '</table></fieldset>';
		if ($calc->activated) {
			$html .= $this->ping($calc);
		}
		return TRUE;
	}



	function ping ($calc) {

		//require('./Credentials.php');	// where service URL, account, license key are set
		$html = '';
		$this->newATConfig($calc);

		if(!class_exists('TaxServiceSoap')) require (VMAVALARA_CLASS_PATH.DS.'TaxServiceSoap.class.php');
		$client = new TaxServiceSoap('Development');

		try
		{
			if(!class_exists('PingResult')) require (VMAVALARA_CLASS_PATH.DS.'PingResult.class.php');
			$result = $client->ping("TEST");
			vmDebug('Ping ResultCode is: '. $result->getResultCode() );

			if(!class_exists('SeverityLevel')) require (VMAVALARA_CLASS_PATH.DS.'SeverityLevel.class.php');
			if($result->getResultCode() != SeverityLevel::$Success)	// call failed
			{
				foreach($result->Messages() as $msg)
				{
					$html .= $msg->Name().": ".$msg->Summary()."<br />";
				}

			}
			else // successful calll
			{
				vmdebug('Ping Version is: '. $result->getVersion() );
			}
		}
		catch(SoapFault $exception)
		{

			$err = "Exception: ";
			if($exception)
				$err .= $exception->faultstring;

			$err .='<br />';
			$err .= $client->__getLastRequest().'<br />';
			$err .= $client->__getLastResponse().'<br />';
			vmError($err);
		}

		return $html;
	}

	static $validatedAddresses = NULL;

	private function fillValidateAvalaraAddress($calc){

		if(!isset(self::$validatedAddresses)){

			$vmadd = $this->getShopperData();

			if(!empty($vmadd)){
				$config = $this->newATConfig($calc);
			//	vmdebug('fillTestAvalaraAddress $vmadd not empty',$vmadd);
				if(!class_exists('AddressServiceSoap')) require (VMAVALARA_CLASS_PATH.DS.'AddressServiceSoap.class.php');
				$client = new AddressServiceSoap('Development',$config);
				//$this->newATConfig($calc);

				if(!class_exists('Address')) require (VMAVALARA_CLASS_PATH.DS.'Address.class.php');
				$address = new Address();
				if(isset($vmadd['address_1'])) $address->setLine1($vmadd['address_1']);
				if(isset($vmadd['address_2'])) $address->setLine2($vmadd['address_2']);
				if(isset($vmadd['city'])) $address->setCity($vmadd['city']);
				if(isset($vmadd['state'])) $address->setRegion($vmadd['state']);
				if(isset($vmadd['zip'])) $address->setPostalCode($vmadd['zip']);


				//VmConfig::$echoDebug = TRUE;
				$address->Coordinates = 1;
				$address->Taxability = TRUE;
				$textCase = TextCase::$Mixed;
				$coordinates = 1;
				if(!class_exists('ValidateResult')) require (VMAVALARA_CLASS_PATH.DS.'ValidateResult.class.php');
				if(!class_exists('ValidateRequest')) require (VMAVALARA_CLASS_PATH.DS.'ValidateRequest.class.php');
				if(!class_exists('ValidAddress')) require (VMAVALARA_CLASS_PATH.DS.'ValidAddress.class.php');
				if(!class_exists('SeverityLevel')) require (VMAVALARA_CLASS_PATH.DS.'SeverityLevel.class.php');
				if(!class_exists('Message')) require (VMAVALARA_CLASS_PATH.DS.'Message.class.php');

				try
				{
					$request = new ValidateRequest($address, ($textCase ? $textCase : TextCase::$Default), $coordinates);
					$result = $client->Validate($request);

					//vmdebug('Validate ResultCode is: '. $result->getResultCode());;
					if($result->getResultCode() != SeverityLevel::$Success)
					{
						foreach($result->getMessages() as $msg)
						{
							vmdebug('fillValidateAvalaraAddress ' . $msg->getName().": ".$msg->getSummary()."\n");
							vmdebug('fillValidateAvalaraAddress ERROR',$address);
						}
					}
					else
					{
						self::$validatedAddresses = $result->getvalidAddresses();
						$echo = "";
						foreach($result->getvalidAddresses() as $valid)
						{
							$echo .= "Line 1: ".$valid->getline1()."\n";
							$echo .=  "Line 2: ".$valid->getline2()."\n";
							$echo .=  "Line 3: ".$valid->getline3()."\n";
							$echo .=  "Line 4: ".$valid->getline4()."\n";
							$echo .=  "City: ".$valid->getcity()."\n";
							$echo .=  "Region: ".$valid->getregion()."\n";
							$echo .=  "Postal Code: ".$valid->getpostalCode()."\n";
							$echo .=  "Country: ".$valid->getcountry()."\n";
							$echo .=  "County: ".$valid->getcounty()."\n";
							$echo .=  "FIPS Code: ".$valid->getfipsCode()."\n";
							$echo .=  "PostNet: ".$valid->getpostNet()."\n";
							$echo .=  "Carrier Route: ".$valid->getcarrierRoute()."\n";
							$echo .=  "Address Type: ".$valid->getaddressType()."\n";
							if($coordinates == 1)
							{
								$echo .=  "Latitude: ".$valid->getlatitude()."\n";
								$echo .=  "Longitude: ".$valid->getlongitude()."\n";
							}
						}
						//vmdebug('Normalized Address:',$echo);
					}

				}
				catch(SoapFault $exception)
				{
					$msg = "Exception: ";
					if($exception)
						$msg .= $exception->faultstring;

				 $msg .= "\n";
					$msg .= $client->__getLastRequest()."\n";
					$msg .= $client->__getLastResponse()."\n";
					vmError($msg);
				}

				if(empty(self::$validatedAddresses)){
					self::$validatedAddresses = FALSE;
				}

				//then for BT and/or $cart->STsameAsBT
			} else {
				self::$validatedAddresses = FALSE;
			}
			//vmdebug("Number of addresses fillValidateAvalaraAddress is ", self::$validatedAddresses);
		}

		return self::$validatedAddresses;

	}

	static $stop = FALSE;
	function getTax($calculationHelper,$calc,$destination,$price){

		//if(self::$stop) return self::$stop;

		if(!class_exists('TaxServiceSoap')) require (VMAVALARA_CLASS_PATH.DS.'TaxServiceSoap.class.php');
		if(!class_exists('DocumentType')) require (VMAVALARA_CLASS_PATH.DS.'DocumentType.class.php');
		if(!class_exists('DetailLevel')) require (VMAVALARA_CLASS_PATH.DS.'DetailLevel.class.php');
		if(!class_exists('Line')) require (VMAVALARA_CLASS_PATH.DS.'Line.class.php');
		if(!class_exists('ServiceMode')) require (VMAVALARA_CLASS_PATH.DS.'ServiceMode.class.php');
		if(!class_exists('Line')) require (VMAVALARA_CLASS_PATH.DS.'Line.class.php');
		if(!class_exists('GetTaxRequest')) require (VMAVALARA_CLASS_PATH.DS.'GetTaxRequest.class.php');
		if(!class_exists('GetTaxResult')) require (VMAVALARA_CLASS_PATH.DS.'GetTaxResult.class.php');

		$client = new TaxServiceSoap('Development');
		$request= new GetTaxRequest();
		$origin = new Address();

		//$destination = $this->fillValidateAvalaraAddress($calc);


		//In Virtuemart we have not differenct warehouses, but we have a shipment address
		//So when the vendor has a shipment address, we assume that it is his warehouse
		//Later we can combine products with shipment addresses for different warehouse (yehye, future music)
		//But for now we just use the BT address
		if (!class_exists ('VirtueMartModelVendor')) require(JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'vendor.php');

		$userId = VirtueMartModelVendor::getUserIdByVendorId ($calc->virtuemart_vendor_id);
		$userModel = VmModel::getModel ('user');
		$virtuemart_userinfo_id = $userModel->getBTuserinfo_id ($userId);
		// this is needed to set the correct user id for the vendor when the user is logged
		$userModel->getVendor($calc->virtuemart_vendor_id);
		$vendorFieldsArray = $userModel->getUserInfoInUserFields ('mail', 'BT', $virtuemart_userinfo_id, FALSE, TRUE);
		$vendorFields = $vendorFieldsArray[$virtuemart_userinfo_id];
		//vmdebug('my vendor fields',$vendorFields);
		$origin->setLine1($vendorFields['fields']['address_1']['value']);
		$origin->setLine2($vendorFields['fields']['address_2']['value']);
		$origin->setCity($vendorFields['fields']['city']['value']);

		$origin->setRegion($vendorFields['fields']['virtuemart_state_id']['state_2_code']);
		$origin->setPostalCode($vendorFields['fields']['zip']['value']);

		$request->setOriginAddress($origin);	      //Address
		$request->setDestinationAddress	($destination);     //Address
		//vmdebug('The date',$origin,$destination);
	//	$request->setCompanyCode($calc->account);         // Your Company Code From the Dashboard
		$request->setCompanyCode($calc->company_code);

		$request->setDocType(DocumentType::$SalesInvoice);   	// Only supported types are SalesInvoice or SalesOrder

		$request->setDocCode('PHPINV999');             //    invoice number

		$request->setDocDate(date('Y-m-d'));           //date

		//$request->setSalespersonCode("");             // string Optional
		$request->setCustomerCode("Cust123");        //string Required
		$request->setCustomerUsageType("");   //string   Entity Usage
		$request->setDiscount(0.00);            //decimal
	//	$request->setPurchaseOrderNo("");     //string Optional
		$request->setExemptionNo("");         //string   if not using ECMS which keys on customer code

		$request->setDetailLevel('Tax');         //Summary or Document or Line or Tax or Diagnostic

	//	$request->setReferenceCode1("");       //string Optional
	//	$request->setReferenceCode2("");       //string Optional
	//	$request->setLocationCode("");        //string Optional - aka outlet id for tax forms


/////////////////////////////////////////

		$products= array();
		vmdebug('getTax price',$price);
		if($calculationHelper->inCart){

			if(!class_exists('VirtueMartCart')) require(JPATH_VM_SITE.DS.'helpers'.DS.'cart.php');
			$cart = VirtueMartCart::getCart();
			//vmdebug('my Cart',$cart);
			$products = $cart->products;
			$prices = $calculationHelper->getCartPrices();
			foreach($products as $k => $product){

				//$prices = $calculationHelper->_cartPrices[$k];
				if(!empty($prices[$k]['discountedPriceWithoutTax'])){
					$price = $prices[$k]['discountedPriceWithoutTax'];
				} else if(!empty($prices[$k]['basePriceVariant'])){
					$price = $prices[$k]['basePriceVariant'];
				} else {
					vmdebug('Hmmm there is no price in getTax for product '.$k.' ',$prices);
					$price = 0.0;
				}
				//$price = empty($cart->pricesUnformatted[$k]['discountedPriceWithoutTax'])? $cart->pricesUnformatted[$k]['basePrice'] : $cart->pricesUnformatted[$k]['discountedPriceWithoutTax'] ;
				$product->price = $price;
				//vmdebug('Avalara set price '.$price);
				if(!empty($price[$k]['discountAmount'])){
					$product->discount = $price[$k]['discountAmount'];
				} else {
					$product->discount = FALSE;
				}
			}
		} else {

			$calculationHelper->_product->price = $price;

			$products[0] = $calculationHelper->_product;
			if(!isset($products[0]->amount)){
				$products[0]->amount = 1;
			}

			//vmdebug('my product $price'.$price);
			if(isset($calculationHelper->productPrices['discountAmount'])){
				$products[0]->discount = $calculationHelper->productPrices['discountAmount'];
			} else {
				$products[0]->discount = FALSE;
			}
		}

		$lines = array();
		$n = 0;
		$lineNumbersToCartProductId = array();
		foreach($products as $k=>$product){
			$n++;
			$lineNumbersToCartProductId[$n] = $k;
			$line1 = new Line();
			$line1->setNo ($n);                  //string  // line Number of invoice
			$line1->setItemCode($product->product_sku);            //string
			$line1->setDescription("Invoice Calculated From PHP SDK");         //string
			$line1->setTaxCode("");             //string
			$line1->setQty($product->amount);                 //decimal
			$line1->setAmount($product->price);              //decimal // TotalAmmount


			$line1->setDiscounted($product->discount);          //boolean
			$line1->setRevAcct("");             //string
			$line1->setRef1("");                //string
			$line1->setRef2("");                //string
			$line1->setExemptionNo("");         //string
			$line1->setCustomerUsageType("");   //string

			$lines[] = $line1;
			//vmdebug('avalaragetTax ',$origin,$request);
		}
		//vmdebug('avalaragetTax setLines',$lines);
		$request->setLines($lines);

		$totalTax = 0.0;
		try
		{
			if(!class_exists('TaxLine')) require (VMAVALARA_CLASS_PATH.DS.'TaxLine.class.php');
			if(!class_exists('TaxDetail')) require (VMAVALARA_CLASS_PATH.DS.'TaxDetail.class.php');

			$getTaxResult = $client->getTax($request);
			/*
			 * [0] => getDocCode
    [1] => getAdjustmentDescription
    [2] => getAdjustmentReason
    [3] => getDocDate
    [4] => getTaxDate
    [5] => getDocType
    [6] => getDocStatus
    [7] => getIsReconciled
    [8] => getLocked
    [9] => getTimestamp
    [10] => getTotalAmount
    [11] => getTotalDiscount
    [12] => getTotalExemption
    [13] => getTotalTaxable
    [14] => getTotalTax
    [15] => getHashCode
    [16] => getVersion
    [17] => getTaxLines
    [18] => getTotalTaxCalculated
    [19] => getTaxSummary
    [20] => getTaxLine
    [21] => getTransactionId
    [22] => getResultCode
    [23] => getMessages
			 */
			//vmdebug( 'GetTax is: '. $getTaxResult->getResultCode(),$getTaxResult);

			if ($getTaxResult->getResultCode() == SeverityLevel::$Success)
			{
				//vmdebug("DocCode: ".$request->getDocCode() );
				//vmdebug("DocId: ".$getTaxResult->getDocId()."\n");

				vmdebug("TotalAmount: ".$getTaxResult->getTotalAmount() );

				$totalTax = $getTaxResult->getTotalTax();
				vmdebug( "TotalTax: ".$totalTax );

				//vmdebug('my tax result ',$getTaxResult);


				foreach($getTaxResult->getTaxLines() as $ctl)
				{
					if($calculationHelper->inCart){
						$prices[$lineNumbersToCartProductId[$ctl->getNo()]]['taxAmount'] = $ctl->getTax();
					}
					vmdebug( "     Line: ".$ctl->getNo()." Tax: ".$ctl->getTax()." TaxCode: ".$ctl->getTaxCode());

					foreach($ctl->getTaxDetails() as $ctd)
					{
						//vmdebug( "          Juris Type: ".$ctd->getJurisType()."; Juris Name: ".$ctd->getJurisName()."; Rate: ".$ctd->getRate()."; Amt: ".$ctd->getTax() );
					}

				}

				if($calculationHelper->inCart){
					$calculationHelper->setCartPrices($prices);
				}

			}
			else
			{
				foreach($getTaxResult->getMessages() as $msg)
				{
					vmError($msg->getName().": ".$msg->getSummary());
				}
			}

		}
		catch(SoapFault $exception)
		{
			$msg = "Exception: ";
			if($exception)
				$msg .= $exception->faultstring;

			vmdebug( $msg.'<br />'.$client->__getLastRequest().'<br />'.$client->__getLastResponse());

		}
		//self::$stop = $totalTax;

		return $totalTax;
	}


	function newATConfig($calc){

		if(!class_exists('TextCase')) require (VMAVALARA_CLASS_PATH.DS.'TextCase.class.php');

		$__wsdldir = VMAVALARA_CLASS_PATH."/wsdl";
		$standard = array(
			'url'       => 'no url specified',
			'addressService' => '/Address/AddressSvc.asmx',
			'taxService' => '/Tax/TaxSvc.asmx',
			'batchService'=> '/Batch/BatchSvc.asmx',
			'avacertService'=> '/AvaCert/AvaCertSvc.asmx',
			'addressWSDL' => 'file://'.$__wsdldir.'/Address.wsdl',
			'taxWSDL'  => 'file://'.$__wsdldir.'/Tax.wsdl',
			'batchWSDL'  => 'file://'.$__wsdldir.'/BatchSvc.wsdl',
			'avacertWSDL'  => 'file://'.$__wsdldir.'/AvaCertSvc.wsdl',
			'account'   => '<your account number here>',
			'license'   => '<your license key here>',
			'adapter'   => 'avatax4php,5.10.0.0',
			'client'    => 'AvalaraPHPInterface,1.0',
			'name'    => 'PHPAdapter',
			'TextCase' => TextCase::$Mixed,
			'trace'     => TRUE);

		//VmConfig::$echoDebug = TRUE;
		//if(!is_object())vmdebug($calc);
		if(!class_exists('ATConfig')) require (VMAVALARA_CLASS_PATH.DS.'ATConfig.class.php');
		if($this->_dev){

			$devValues = array(
				'url'       => 'https://development.avalara.net',
				'account'   => $calc->account,
				'license'   => $calc->license,
				'trace'     => TRUE); // change to false for production
			$resultingConfig = array_merge($standard,$devValues);
			$config = new ATConfig('Development', $resultingConfig);

		} else {

			$prodValues = array(
				'url'       => 'https://avatax.avalara.net',
				'account'   => $calc->account,
				'license'   => $calc->license,
				'trace'     => FALSE);
			$resultingConfig = array_merge($standard,$prodValues);
			$config = new ATConfig('Production', $resultingConfig);

		}


		return $config;
	}

	static $vmadd = NULL;
	private function getShopperData(){

		if(!isset(self::$vmadd)){
			//We need for the tax calculation the shipment Address
			//We have this usually in our cart.
			if (!class_exists('VirtueMartCart')) require(JPATH_VM_SITE . DS . 'helpers' . DS . 'cart.php');
			$cart = VirtueMartCart::getCart();

			//Test first for ST
			if($cart->STsameAsBT){
				if(!empty($cart->BT)) $vmadd = $cart->BT;
			} else if(!empty($cart->ST)){
				$vmadd = $cart->ST;
			} else {
				if(!empty($cart->BT)) $vmadd = $cart->BT;
			}
			//vmdebug('getShopperData',$vmadd);
			//Maybe the user is logged in, but has no cart yet.
			if(empty($vmadd)){
				$jUser = JFactory::getUser ();
				$userModel = VmModel::getModel('user');
				$userModel -> setId($jUser->id);
				$BT_userinfo_id = $userModel->getBTuserinfo_id();
				//Todo check if we actually need this fallback
				//vmdebug('getShopperData cart data was empty',$vmadd);
			}

			if(empty($vmadd)){
				vmInfo('We cannot calculate your tax, please enter your shipment address');
				$vmadd=FALSE;
			}
			self::$vmadd = $vmadd;
		}


		return self::$vmadd;
	}

	public function plgVmInterpreteMathOp ($calculationHelper, $rule, $price,$revert){

		$rule = (object)$rule;

		$mathop = $rule->calc_value_mathop;
		$value = $rule->calc_value;
		$currency =  $rule->calc_currency;

		//vmdebug('plgVmInterpreteMathOp',$calculationHelper);
		$tax = 0.0;

		if ($mathop=='avalara') {
			$requestedProductId = JRequest::getInt('virtuemart_product_id');
			//vmdebug('plgVmInterpreteMathOp',$calculationHelper);

			if(isset($calculationHelper->_product)){
				$productId = $calculationHelper->_product->virtuemart_product_id;
			} else {
				$productId = $requestedProductId;
				//vmTrace('plgVmInterpreteMathOp avalara');
			}

			//VmConfig::$echoDebug = TRUE;
			//vmdebug('plgVmInterpreteMathOp avalara $productId '.$productId.' $requestedProductId '.$requestedProductId);

			if($productId==$requestedProductId or $calculationHelper->inCart ){
				VmTable::bindParameterable ($rule, $this->_xParams, $this->_varsToPushParam);
				if(empty($this->addresses)){
					$this->addresses = $this->fillValidateAvalaraAddress($rule);
				}

				//vmdebug('plgVmInterpreteMathOp $addresses ',$addresses);
				if($this->addresses){

					$tax = $this->getTax( $calculationHelper,$rule,$this->addresses[0],$price);

				}
			}


			//vmInfo("Number of addresses returned is ".$addresses);
		}

		if($revert){
			$tax = -$tax;
		}
		//vmdebug('plgVmInterpreteMathOp myTax '.$price,$tax);
		return $price + (float)$tax;
	}

	public function plgVmInGatherEffectRulesBill(&$calculationHelper,&$rules){

		//vmdebug('plgVmInGatherEffectRulesBill',$rules);
		/*
		$view = JRequest::getWord('view',0);
		if($calculationHelper->inCart){

		}
		 */
		return FALSE;
	}

	/**
	 * We can only calculate it for the productdetails view
	 * @param $calculationHelper
	 * @param $rules
	 */
	public function plgVmInGatherEffectRulesProduct(&$calculationHelper,&$rules){


		//If in cart, the tax is calculated per bill, so the rule per product must be removed
		if($calculationHelper->inCart){
			foreach($rules as $k=>$rule){
				if($rule['calc_value_mathop']=='avalara'){
					unset($rules[$k]);
				}
			}
		}
		//vmdebug('plgVmInGatherEffectRulesProduct',$rules);
	}


	public function plgVmStorePluginInternalDataCalc(&$data){


		//$table = $this->getTable('calcs');
		if (!class_exists ('TableCalcs')) {
			require(JPATH_VM_ADMINISTRATOR . DS . 'tables' . DS . 'calcs.php');
		}
		$db = JFactory::getDBO ();
		$table = new TableCalcs($db);
		$table->setUniqueName('calc_name');
		$table->setObligatoryKeys('calc_kind');
		$table->setLoggable();
		$table->setParameterable ($this->_xParams, $this->_varsToPushParam);
		$table->bindChecknStore($data);

	}

	public function plgVmGetPluginInternalDataCalc(&$calcData){

		$calcData->setParameterable ($this->_xParams, $this->_varsToPushParam);

		if (!class_exists ('VmTable')) {
			require(JPATH_VM_ADMINISTRATOR . DS . 'helpers' . DS . 'vmtable.php');
		}
		VmTable::bindParameterable ($calcData, $this->_xParams, $this->_varsToPushParam);
		return TRUE;

	}




	public function plgVmDeleteCalculationRow($id){
		$this->removePluginInternalData($id);
	}
}

// No closing tag
