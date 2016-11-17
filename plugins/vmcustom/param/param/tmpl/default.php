<?php
defined('_JEXEC') or die('Restricted access');
/**
* Param: Virtuemart 2 customfield plugin
* Version: 2.0.5 (2013.04.01)
* Author: Usov Dima
* Copyright: Copyright (C) 2012-2013 usovdm
* License GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
* http://myext.eu
**/
if(!isset($viewData)) $viewData = $params; // VM 2.0.5 Fix
$html = '<div class="product-field-'.$viewData->virtuemart_custom_id.'">';
// $html .= '<div class="mcf-fields-title">'.$viewData->custom_title.'</div>';
// $values = $viewData->ft == 'int' ? array($viewData->intvalue) : explode('|',substr($viewData->value,1,-1));
$values = $viewData->value;
$html .='<div class="product-fields-value">';
if(count($values) > 0){
	$html .= '<ul>';
	foreach($values as &$v){
		if($viewData->ft == 'int'){
			echo '<li>'.$v->intval.'</li>';
		}else{
			echo '<li>'.$v->value.'</li>';
		}
	}unset($v);
	$html .= '</ul>';
}
$html .='</div></div>';
if(count($values) > 0){
	echo $html;
}