<?php 
/*
# ------------------------------------------------------------------------
# Templates for Joomla 2.5 - Joomla 3.5
# ------------------------------------------------------------------------
# Copyright (C) 2011-2013 Jtemplate.ru. All Rights Reserved.
# @license - PHP files are GNU/GPL V2.
# Author: Makeev Vladimir
# Websites:  http://www.jtemplate.ru 
# ---------  http://code.google.com/p/jtemplate/   
# ------------------------------------------------------------------------
*/
// no direct access
defined('_JEXEC') or die;

//baza bxSlider module
$document 					=& JFactory::getDocument();
$document->addStyleSheet(JURI::base() . 'modules/mod_jt_bxslider_vm_product/css/jquery.bxslider.css');
$jt_jquery_ver				= $params->get('jt_jquery_ver', '1.8.3');
$jt_load_jquery				= (int)$params->get('jt_load_jquery', 0);
$jt_load_easing				= (int)$params->get('jt_load_easing', 0);
$jt_load_bxslider			= (int)$params->get('jt_load_bxslider', 1);


if ($jt_load_jquery  > 0) {
	$document->addCustomTag('<script type = "text/javascript" src = "http://ajax.googleapis.com/ajax/libs/jquery/'.$jt_jquery_ver.'/jquery.min.js"></script>');		
}
/*
if ($jt_load_easing  > 0) { 
	$document->addCustomTag('<script type = "text/javascript" src = "'.JURI::root().'modules/mod_jt_bxslider_vm_product/js/jquery.easing.1.3.js"></script>'); 
} 
*/
if ($jt_load_bxslider > 0) {	
	$document->addCustomTag('<script type = "text/javascript" src = "'.JURI::root().'modules/mod_jt_bxslider_vm_product/js/jquery.bxslider.min.js"></script>');
	$document->addCustomTag('<script type = "text/javascript">if (jQuery) jQuery.noConflict();</script>');
}

$moduleclass_sfx			= $params->get('moduleclass_sfx');
$jt_id						= $params->get('jt_id');
// width-height all img !!!
//$jt_width					= (int)$params->get('jt_width' , 300);
//$jt_height				= (int)$params->get('jt_height', 200);

// bxSlider options - http://bxslider.com/options
$jt_mode					= $params->get('jt_mode', 'horizontal');
$jt_speed					= (int)$params->get('jt_speed', 500);
$jt_controls				= $params->get('jt_controls', 'true');
$jt_auto					= $params->get('jt_auto', 'false');
$jt_autohover				= $params->get('jt_autohover', 'false');
$jt_pause					= (int)$params->get('jt_pause', 1500);
$jt_auto_controls			= $params->get('jt_auto_controls', 'true');
$jt_auto_delay				= (int)$params->get('jt_auto_delay', 0);
$jt_pager					= $params->get('jt_pager', 'true');
$jt_pager_type 				= $params->get('jt_pager_type', 'full');
$jt_pager_saparator			= $params->get('jt_pager_saparator', '/');
$jt_slide_width				= (int)$params->get('jt_slide_width', 100);
$jt_slide_margin			= (int)$params->get('jt_slide_margin', 10);
$jt_adaptive_height			= $params->get('jt_adaptive_height', 'false');
$jt_adaptive_height_speed	= (int)$params->get('jt_adaptive_height_speed', 500);
//$jt_ticker 					= $params->get('jt_ticker', 'false');
//$jt_ticker_hover			= $params->get('jt_ticker_hover', 'false');
//$jt_random_start			= $params->get('jt_random_start', 'false');
$jt_min_slides				= (int)$params->get('jt_min_slides', 1);
$jt_max_slides				= (int)$params->get('jt_max_slides', 4);
$jt_move_slides				= (int)$params->get('jt_move_slides', 0);

if ($jt_move_slides < $jt_min_slides OR $jt_move_slides > $jt_max_slides ) {
	$jt_move_slides = 0;
}


//baza VM2

if (!class_exists( 'VmConfig' )) require(JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_virtuemart'.DS.'helpers'.DS.'config.php');

VmConfig::loadConfig();
VmConfig::loadJLang('mod_jt_bxslider_vm_product', true);

// Setting
$max_items					= (int)$params->get( 'max_items', 10 ); //maximum number of items to display
//$layout 					= $params->get('layout','default');
$category_id 				= $params->get( 'virtuemart_category_id', null ); // Display products from this category only
$filter_category			= (bool)$params->get( 'filter_category', 0 ); // Filter the category
$show_price 				= (bool)$params->get( 'show_price', 1 ); // Display the Product Price?
$Product_group 				= $params->get( 'product_group', 'featured'); 
$show_addtocart 			= (bool)$params->get( 'show_addtocart', 1 ); // Display the "Add-to-Cart" Link? 

$mainframe 					= JFactory::getApplication();
$virtuemart_currency_id 	= $mainframe->getUserStateFromRequest( "virtuemart_currency_id", 'virtuemart_currency_id',JRequest::getInt('virtuemart_currency_id',0) );
$key 						= 'products'.$category_id.'.'.$max_items.'.'.$filter_category.'.'.$show_price.'.'.$show_addtocart.'.'.$Product_group.'.'.$virtuemart_currency_id;
$cache						= JFactory::getCache('mod_jt_bxslider_vm_product', 'output');

if (!($output = $cache->get($key))) {
	ob_start();
	// Try to load the data from cache.

	/* Load  VM fanction */
	if (!class_exists( 'mod_jt_bxslider_vm_product' )) require('helper.php');

	$vendorId = JRequest::getInt('vendorid', 1);

	if ($filter_category ) $filter_category = TRUE;

	$productModel = VmModel::getModel('Product');

	$products = $productModel->getProductListing($Product_group, $max_items, $show_price, true, false,$filter_category, $category_id);
	$productModel->addImages($products);

	$totalProd = 		count( $products);
	if(empty($products)) return false;
	$currency = CurrencyDisplay::getInstance( );

	if ($params->get('jt_vm_jscss', 1) > 0) {
		vmJsApi::jPrice();
		vmJsApi::cssSite();
	} 

	/* Load tmpl default */
//require(JModuleHelper::getLayoutPath('mod_jt_bxslider_vm_product',$layout));
require JModuleHelper::getLayoutPath('mod_jt_bxslider_vm_product', $params->get('layout', 'default'));
	$output = ob_get_clean();
	$cache->store($output, $key);
}
echo $output;
?>