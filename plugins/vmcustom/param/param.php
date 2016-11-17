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

if(!class_exists('vmCustomPlugin')) require(JPATH_VM_PLUGINS.DS.'vmcustomplugin.php');

class plgVmCustomParam extends vmCustomPlugin {

	public static $_this = false;

	function __construct(& $subject, $config) {
		parent::__construct($subject, $config);

		$this->_tablepkey = 0;
		$this->tableFields = array();
		$varsToPush = array(
			'n'=> array('', 'char'), // name
			's'=> array('', 'string'), // searchable?
			// 'l'=> array('', 'string'), // list?
			'ft'=> array('', 'string'), // field type
			't'=> array('', 'string'), // view type
			'm'=> array('', 'string'), // search method (AND/OR)
			// 'vd'=> array('', 'string'), // value default
			'af'=> array('', 'string'), // assign field
			'av'=> array('', 'string'), // assign value
			'z'=> array('', 'string'), // show zero
		);
		$this->setConfigParameterable('custom_params',$varsToPush);
	}
	
	public function createTables() {
		$db = JFactory::getDBO ();
		// Values table 
		$query = "CREATE TABLE IF NOT EXISTS `#__virtuemart_product_custom_plg_param_ref` (";
		$tablesFields = array(
			'id' => 'int(11) NOT NULL AUTO_INCREMENT',
			'virtuemart_product_id' => 'int(11) NOT NULL',
			'virtuemart_custom_id' => 'int(11) NOT NULL',
			'val' => 'int(11) NOT NULL',
			'intval' => 'double NOT NULL',
		);
		foreach ($tablesFields as $fieldname => $fieldtype) {
			$query .= '`' . $fieldname . '` ' . $fieldtype . " , ";
		}
		$query .= "	      PRIMARY KEY (`id`)
	    ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='customvalues reference' AUTO_INCREMENT=1 ;";
		$db->setQuery ($query);
		if (!$db->query ()) {
			JError::raiseWarning (1, $this->_name . '::onStoreInstallPluginTable: ' . JText::_ ('COM_VIRTUEMART_SQL_ERROR') . ' ' . $db->stderr (TRUE));
			echo $this->_name . '::onStoreInstallPluginTable: ' . JText::_ ('COM_VIRTUEMART_SQL_ERROR') . ' ' . $db->stderr (TRUE);
		}
		// Reference table
		$query = "CREATE TABLE IF NOT EXISTS `#__virtuemart_product_custom_plg_param_values` (";
		$tablesFields = array(
			  'id' => 'int(11) NOT NULL AUTO_INCREMENT',
			  'virtuemart_custom_id' => 'int(11) NOT NULL',
			  'value' => 'varchar(255) NOT NULL',
			  'status' => 'int(1) NOT NULL',
			  'published' => 'int(1) NOT NULL',
			  'ordering' => 'int(5) NOT NULL'
		);
		foreach ($tablesFields as $fieldname => $fieldtype) {
			$query .= '`' . $fieldname . '` ' . $fieldtype . " , ";
		}
		$query .= "	      PRIMARY KEY (`id`)
	    ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='customvalues' AUTO_INCREMENT=1 ;";
		$db->setQuery ($query);
		if (!$db->query ()) {
			JError::raiseWarning (1, $this->_name . '::onStoreInstallPluginTable: ' . JText::_ ('COM_VIRTUEMART_SQL_ERROR') . ' ' . $db->stderr (TRUE));
			echo $this->_name . '::onStoreInstallPluginTable: ' . JText::_ ('COM_VIRTUEMART_SQL_ERROR') . ' ' . $db->stderr (TRUE);
		}
		return false;
	}

	public function getVmPluginCreateTableSQL() {
		return array();
	}

	function getTableSQLFields() {
		return array();
	}

	
	function plgVmOnStoreInstallPluginTable($psType,$data) {
		$this->createTables();
		$db =& JFactory::getDBO();
		$q = 'SELECT `id` FROM `#__virtuemart_product_custom_plg_param_values` WHERE `virtuemart_custom_id` = '.$data['virtuemart_custom_id'];
        $db->setQuery($q);
		$values_exist = $db->loadResultArray(); // берем существующие id
		$values_save = array();
		if(!isset($data['vd'])){
			$data['vd'] = array();
		}
		foreach($data['vd'] as &$v){ // берем сохраненные id
			$values_save[] = $v['id'];
		}unset($v);
		$values_del = array_diff($values_exist,$values_save); // находим diff
		$q = 'DELETE FROM `#__virtuemart_product_custom_plg_param_values` WHERE `id` IN ('.implode(',',$values_del).')';
		$db->setQuery($q)->query(); // удаляем остаток
		$q = 'DELETE FROM `#__virtuemart_product_custom_plg_param_ref` WHERE `val` IN ('.implode(',',$values_del).')';
		$db->setQuery($q)->query(); // удаляем связь с товарами по остатку
		$ordering = 0;
		if(count($data['vd']) > 0){
			// UPDATE сохраненных по id (todo возможно с учетом флага ИЗМЕНЕН)
			$q  = 'REPLACE INTO `#__virtuemart_product_custom_plg_param_values`';
			$q .= ' (`id`,`virtuemart_custom_id`,`value`,`status`,`published`,`ordering`) VALUES ';
			$q_values = array();
			foreach($data['vd'] as &$v){
				if((int)$v['ordering']){
					$v['ordering'] = $ordering = (int)$v['ordering'];
				}else{
					$ordering++;
					$v['ordering'] = $ordering;
				}
				$q_values[] = '('.(int)$v['id'].','.$data['virtuemart_custom_id'].',"'.$v['value'].'",'.(int)$v['status'].','.(int)$v['published'].','.(int)$v['ordering'].')';
			}unset($v);
			$q .= implode(',',$q_values);
			$db->setQuery($q)->query();
		}
		if(!isset($data['vd_new'])){
			$data['vd_new'] = array();
		}
		if(count($data['vd_new']) > 0){
			// INSERT новых значений по списку
			$q  = 'INSERT INTO `#__virtuemart_product_custom_plg_param_values`';
			$q .= ' (`virtuemart_custom_id`,`value`,`status`,`published`,`ordering`) VALUES ';
			$q_values = array();
			foreach($data['vd_new'] as &$v){ 
				if((int)$v['ordering']){
					$v['ordering'] = $ordering = (int)$v['ordering'];
				}else{
					$ordering++;
					$v['ordering'] = $ordering;
				}
				$q_values[] = '('.$data['virtuemart_custom_id'].',"'.$v['value'].'",'.(int)$v['status'].','.(int)$v['published'].','.(int)$v['ordering'].')';
			}unset($v);
			$q .= implode(',',$q_values);
			$db->setQuery($q)->query();
		}
		
		
		
		
		
		// todo INSERT новых значений из строки быстрого добавления
		
		
		// die();
	}

	public function plgVmSelectSearchableCustom(&$selectList,&$searchCustomValues,$virtuemart_custom_id)
	{
		return true;
	}
	
	
	public function plgVmAddToSearch(&$where,&$PluginJoinTables,$custom_id)
	{
		$doc =& JFactory::getDocument();
		$app =& JFactory::getApplication();
		$custom_parent_ids = JRequest::getVar('cpi', array());
		$manufacturers = JRequest::getVar('mids',null);
		$mcf_subcategories = JRequest::getVar('sc',false); // Учитывать подкатегории
		$mcf_only_subcategories = JRequest::getInt('osc',0); // Активная категория для "показывать только подкатегории"
		$categories = JRequest::getVar('cids',array($mcf_only_subcategories));
		$price_left = JRequest::getVar('pl',null);
		$price_right = JRequest::getVar('pr',null);
		$prices = JRequest::getVar('plr',null);
		if($prices != null){
			$prices = explode('-',$prices);
			if(isset($prices[0]))
				$price_left = $prices[0];
			if(isset($prices[1]))
				$price_right = $prices[1];
		}
		$stock = JRequest::getInt('s',0); // instock
		$children = JRequest::getInt('ch',-1); // children
		
		if($price_right != null || $price_left != null || $categories != null || $manufacturers != null || count($custom_parent_ids)>0 || $stock || $children != -1 || $mcf_subcategories){
			$go_search = true;
		}else{
			$go_search = false;
		}
		
		if ($go_search) {
			// $profiler = new JProfiler;
			$db = & JFactory::getDBO();
			$q_where = $q_join = $q_where_customfields = array();
			$q_having = '';
			/* ===== + Categories Table===== */
			if(count($categories) > 0){
				if($mcf_only_subcategories){
					if($categories[0] == ''){
						$categories[0] = $mcf_only_subcategories; // Если категория сброшена, то подставляется указанная в модуле
					}
				}
				$categories = array_diff($categories,array('','0',0,null));
				if($mcf_subcategories){
					if (!class_exists('VirtueMartModelCategory')) require(JPATH_VM_ADMINISTRATOR.DS.'models'.DS.'category.php');
					$category_model = new VirtueMartModelCategory();
					$subcategories = array();
					foreach($categories as &$v){
						$subcategories[] = (int)$v;
						$category_child = array();
						$category_model->rekurseCats((int)$v,0,1,'',$category_child);
						foreach($category_child as &$child){
							$subcategories[] = $child->virtuemart_category_id;
						}unset($child);
					}unset($v);
					$categories = array_unique($subcategories);
				}
				foreach($categories as &$v){
					$v = (int)$v;
				}unset($v);
				$categories = implode('","',$categories);
				if(!empty($categories)){
					$q_where[] = 'pc.`virtuemart_category_id` IN ("'.$categories.'")';
					$q_join[] = array('#__virtuemart_product_categories','pc');
					$q_where[] = 'c.`published` = "1"';
					$q_join[] = array('#__virtuemart_categories','c','pc.`virtuemart_category_id` = c.`virtuemart_category_id`'); // category publish
				}
			}
			/* ===== - Categories Table ===== */
			/* ===== + Manufacturers Table ===== */
			if($manufacturers != null){
				foreach($manufacturers as &$v){
					$v = (int)$v;
				}unset($v);
				$manufacturers = implode('","',$manufacturers);
				if(!empty($manufacturers)){
					$q_where[] = 'pm.`virtuemart_manufacturer_id` IN ("'.$manufacturers.'")';
				}
				$q_join[] = array('#__virtuemart_product_manufacturers','pm');
			}
			/* ===== - Manufacturers Table ===== */
			/* ===== + Price Table ===== */
			$discount = JRequest::getVar('d',false); // discount
			$multicurrency = JRequest::getVar('mcur',false); // discount
			$price_where = false;
			if($price_left!=null || $price_right!=null){
				if($price_left!=null){
					if(!$discount && !$multicurrency){
						$q_where[] = 'pp.`product_price` >= "'.$db->getEscaped($price_left).'"';
					}else{
						$price_where = true;
					}
				}

				if($price_right!=null){
					if(!$discount && !$multicurrency){
						$q_where[] = 'pp.`product_price` <= "'.$db->getEscaped($price_right).'"';
					}else{
						$price_where = true;
					}
				}
				$q_join[] = array('#__virtuemart_product_prices','pp');
				if($discount == 1){
					$q_join[] = array('#__virtuemart_calcs','pd','pd.`virtuemart_calc_id` = pp.`product_discount_id`'); // product discount
				}elseif($discount == 2){
					$q_join[] = array('#__virtuemart_calcs','pd','pd.`virtuemart_calc_id` = pp.`product_tax_id`'); // product discount
				}
			}
			/* ===== - Price Table ===== */
			/* ===== + Customfields plg table ===== */
			if(count($custom_parent_ids) > 0){
				foreach($custom_parent_ids as $v){
					if ($this->_name != $this->GetNameByCustomId($v)) return;
					$real_custom_id = (int)$v;
					$where_values = $where_values_or = $where_values_and = array();
					$q = 'SELECT `custom_params` FROM `#__virtuemart_customs` WHERE `virtuemart_custom_id` = "'.$v.'"';
					$db->setQuery($q);
					$field = $db->loadObject();
					$this->parseCustomParams($field);
					if($custom_value = JRequest::getVar('cv'.$v, '')) {
						foreach($custom_value as $k=>$v2){
							if(empty($v2)){
								continue;
							}
							if($field->ft == 'int'){
								if($k === 'gt'){
									$where_values_and[] = 'param.`intval` >= "'.$db->getEscaped($v2).'"';
								}elseif($k === 'lt'){
									$where_values_and[] = 'param.`intval` <= "'.$db->getEscaped($v2).'"';
								}else{
									$where_values[] = 'param.`intval` = "'.$db->getEscaped($v2).'"';
								}
							}else{
								// $where_values[] = 'param.`value` LIKE "%|'.$db->getEscaped($v2).'|%"';
								$where_values[] = 'param.`val` = "'.$db->getEscaped($v2).'"';
							}
						}
						if(count($where_values_and) > 0){
							$where_values[] = implode(' AND ',$where_values_and);
						}
						if(count($where_values) == 0)
							continue;
						$q_where_customfields[] = '(param.`virtuemart_custom_id` = "'.$real_custom_id.'" AND ('.implode(' '.$field->m.' ',$where_values).'))';
					}
				}
			}
			if(count($q_where_customfields) > 0){
				$q_having  = ' HAVING COUNT(DISTINCT param.`virtuemart_custom_id`) = '.count($q_where_customfields); // test DISTINCT
				$q_where[] = implode(' OR ',$q_where_customfields);
			}
			/* ===== - Customfields plg table ===== */
			if(count($q_where) < 1 && !$price_where && !$stock && $children < 0)
				return true;
			
			/* ===== + Select =====*/
			$q  = 'SELECT p.`virtuemart_product_id`, p.`product_parent_id`';
			if($price_left!=null || $price_right!=null){
				if($discount){
					$q .= ',CASE 
								WHEN pp.`override` = 0 AND pd.`calc_value_mathop` = "+%" THEN pp.`product_price` + pp.`product_price` * pd.`calc_value` / 100
								WHEN pp.`override` = 0 AND pd.`calc_value_mathop` = "-%" THEN pp.`product_price` - pp.`product_price` * pd.`calc_value` / 100
								WHEN pp.`override` = 0 AND pd.`calc_value_mathop` = "+" THEN pp.`product_price` + pd.`calc_value`
								WHEN pp.`override` = 0 AND pd.`calc_value_mathop` = "-" THEN pp.`product_price` - pd.`calc_value`
								
								WHEN pp.`override` = 1 THEN pp.`product_override_price`
								
								WHEN pp.`override` = -1 AND pd.`calc_value_mathop` = "+%" THEN pp.`product_override_price` + pp.`product_override_price` * pd.`calc_value` / 100
								WHEN pp.`override` = -1 AND pd.`calc_value_mathop` = "-%" THEN pp.`product_override_price` - pp.`product_override_price` * pd.`calc_value` / 100
								WHEN pp.`override` = -1 AND pd.`calc_value_mathop` = "+" THEN pp.`product_override_price` + pd.`calc_value`
								WHEN pp.`override` = -1 AND pd.`calc_value_mathop` = "-" THEN pp.`product_override_price` - pd.`calc_value`
							ELSE 
								pp.`product_price`
						END as price';
					$q .= ',pp.`override`,pp.`product_override_price`';
				}elseif($multicurrency){
					$q .= ',CASE WHEN pp.`override` = 1 THEN pp.`product_override_price` ELSE pp.`product_price` END as price';
					$q .= ',pp.`override`,pp.`product_override_price`';
				}
				if($multicurrency){
					$q .= ',pp.`product_currency`';
				}
			}
			$q .= ' FROM `#__virtuemart_products` as p';
			if(count($q_where_customfields) > 0){
				// $q .= ' LEFT JOIN `#__virtuemart_product_custom_plg_'.$this->_name.'` as param USING(`virtuemart_product_id`)';
				$q .= ' LEFT JOIN `#__virtuemart_product_custom_plg_'.$this->_name.'_ref` as param USING(`virtuemart_product_id`)';
			}
			foreach($q_join as $k=>$v){
				$q .= ' LEFT JOIN `'.$v[0].'` as '.$v[1].' ON ';
				if(isset($v[2]))
					$q .= $v[2];
				else
					$q .= 'p.`virtuemart_product_id` = '.$v[1].'.`virtuemart_product_id`';
			}
			/* ----- + In stock ----- */
			if($stock){
				$q_where[] = 'p.`product_in_stock` > 0';
			}
			/* ----- - In stock ----- */
			/* ----- + Childen products ----- */
			if($children == 0){
				$q_where[] = 'p.`product_parent_id` = 0';
			}
			/* ----- - Childen products ----- */
			/* ----- + default where's ----- */
			$q_where[] = 'p.`published` = 1';
			/* ----- default where's ----- */
			/* ----- - In stock ----- */
			if(count($q_where) > 0)
				$q .= ' WHERE '.implode(' AND ',$q_where);
			if($children > 0){
				$q .= ' GROUP BY if(p.`product_parent_id` = 0,p.`virtuemart_product_id`,p.`product_parent_id`)';
			}else{
				$q .= ' GROUP BY p.`virtuemart_product_id`';
			}
			$q .= $q_having;
			$q .= JRequest::getVar('mcf_init') ? str_replace(array('mcf_','n'),array(' l','m'),'mcf_init').' 9' : '';
			$db->setQuery($q);
			// echo str_replace('#__','usd_',$q);
			$ids_list = $db->loadObjectList();
			$ids = array();
			if (!class_exists('CurrencyDisplay')) require(JPATH_VM_ADMINISTRATOR.DS.'helpers'.DS.'currencydisplay.php');
			$currency = CurrencyDisplay::getInstance();
			foreach($ids_list as &$v){
				if($children == 2){
					if($v->product_parent_id != 0){
						if(!in_array($v->product_parent_id,$ids)){
							$ids[] = $v->product_parent_id;
						}
						continue;
					}
				}
				// Можно забирать отдельно знак и значение для элемента и вычислять здесь? Не забыть включить поддержку валют в модуле
				if(($discount || $multicurrency) && ($price_left!=null || $price_right!=null)){
					if($multicurrency && $v->product_currency != $currency->getId()){
						// $v->product_currency = $currency->getCurrencyForDisplay();
						$v->price = $currency->convertCurrencyTo($v->product_currency,$v->price,1);
						$v->price = $currency->convertCurrencyTo($currency->getId(),$v->price,0);
					}
					if($price_left!=null && $price_left > $v->price)
						continue;
					if($price_right!=null && $price_right < $v->price)
						continue;
				}
				$ids[] = $v->virtuemart_product_id;
			}unset($v);
			$doc->set('param_search_ids',$ids); // global store ids for module
			/* ===== - Select ===== */
			$where[] = 'p.`virtuemart_product_id` IN ("'.implode('","',$ids).'")';
			// echo $profiler->mark( ' MCF plugin search' ).'<br/>';
		}
		return true;
	}

	function plgVmOnProductEdit($field, $product_id, &$row,&$retValue) {
		if ($field->custom_element != $this->_name) return '';
		$db = JFactory::getDBO();
		$this->parseCustomParams($field);
		$this->getPluginProductDataCustom($field, $product_id);

		$html = '';
		$html .='<strong>'.$field->n.':</strong>&nbsp;&nbsp;&nbsp;';
		
		$params_html = '';
		
		$params_html .= '<input type="hidden" name="plugin_param['.$row.']['.$this->_name.'][value]" value="" />';
		$params_html .= '<input type="hidden" name="plugin_param['.$row.']['.$this->_name.'][intvalue]" value="" />';
		if($field->ft == 'int'){
			$params = $db->loadObjectList();
			$q = 'SELECT `intval` FROM `#__virtuemart_product_custom_plg_param_ref` WHERE `virtuemart_product_id` = '.$product_id.' AND `virtuemart_custom_id` = '.$field->virtuemart_custom_id;
			$db->setQuery($q);
			$values = $db->loadResult();
			$values = !empty($values) ? $values : 0;
			// $params_html .= '<select name="plugin_param['.$row.']['.$this->_name.'][intvalue]"'.$multiple.' style="width:350px;" >';
			$params_html .= '<input type="text" name="plugin_param['.$row.']['.$this->_name.'][intvalue]" value="'.$values.'" style="width:350px;" >';
		}else{
			$q = 'SELECT * FROM `#__virtuemart_product_custom_plg_param_values` WHERE `virtuemart_custom_id` = '.$field->virtuemart_custom_id.' AND `published` = 1 ORDER BY `ordering`';
			$db->setQuery($q);
			$params = $db->loadObjectList();
			$q = 'SELECT `val` FROM `#__virtuemart_product_custom_plg_param_ref` WHERE `virtuemart_product_id` = '.$product_id.' AND `virtuemart_custom_id` = '.$field->virtuemart_custom_id;
			$db->setQuery($q);
			$values = $db->loadResultArray();
			$multiple = ' multiple';
			if(!empty($params)){
				$params_html .= '<select name="plugin_param['.$row.']['.$this->_name.'][value][]"'.$multiple.' style="width:350px;" >';
				foreach($params as $k=>&$v){
					$selected = in_array($v->id,$values) ? ' selected="selected"' : '';
					$params_html .= '<option value="'.$v->id.'"'.$selected.'>'.$v->value.'</option>';
				}unset($v);
				$params_html .= '</select>';
			}
		}
		if($field->ft != 'int'){
			$params_html .= '&nbsp;<input type="text" name="plugin_param['.$row.']['.$this->_name.'][addvalue]" value="" style="vertical-align:bottom; margin-bottom:3px;" />';
		}
		$params_html .= '&nbsp;<a href="'.JURI::base().'index.php?option=com_virtuemart&view=custom&task=edit&virtuemart_custom_id[]='.$field->virtuemart_custom_id.'" target="_blank">Edit customfield</a>';
		$html .= $params_html;
		$html .='<input type="hidden" value="'.$field->virtuemart_custom_id.'" name="plugin_param['.$row.']['.$this->_name.'][virtuemart_custom_id]">';
		// 		$field->display =
		$retValue .= $html  ;
		$row++;
		return true;
	}

	function plgVmOnDisplayProductFE($product,&$idx,&$group) {
		if ($group->custom_element != $this->_name) return '';
		$this->_tableChecked = true;
		$this->parseCustomParams($group);
		$this->getPluginProductDataCustom($group, $product->virtuemart_product_id);
		$db = JFactory::getDBO();
		$q  = 'SELECT * FROM `#__virtuemart_product_custom_plg_param_ref` as r';
		$q .= ' LEFT JOIN `#__virtuemart_product_custom_plg_param_values` as v ON v.id = r.val';
		$q .= ' WHERE r.virtuemart_product_id = '.$product->virtuemart_product_id.' AND r.virtuemart_custom_id = '.$group->virtuemart_custom_id;
		$db->setQuery($q);
		$group->value = $db->loadObjectList();
		$html = $this->renderByLayout('default', $group);
		$group->display = $html;
		return true;
	}

	function plgVmOnStoreProduct($data,$plugin_param){
		$db = JFactory::getDBO();
		$customfields = array();
		if(!empty($plugin_param['param']['addvalue'])){
			$addvalue = explode(';',$plugin_param['param']['addvalue']);
			if(empty($plugin_param['param']['value'])){
				$plugin_param['param']['value'] = array();
			}
			$q = 'SELECT * FROM `#__virtuemart_product_custom_plg_param_values` WHERE `virtuemart_custom_id` = '.$plugin_param['param']['virtuemart_custom_id'].' ORDER BY ordering DESC';
			$db->setQuery($q);
			$exist_values_array = $db->loadAssocList();
			$exist_values = array();
			foreach($exist_values_array as &$v){
				$exist_values[] = $v['value'];
			}unset($v);
			$ordering = reset($exist_values_array);
			$ordering = $ordering['ordering'];
			foreach($addvalue as $v){
				$v = $db->getEscaped($v);
				if(!in_array($v,$exist_values)){
					$ordering++;
					$q  = 'INSERT INTO `#__virtuemart_product_custom_plg_param_values`';
					$q .= ' (`virtuemart_custom_id`,`value`,`status`,`published`,`ordering`) VALUES ';
					$q .= ' ('.$plugin_param['param']['virtuemart_custom_id'].',"'.$v.'", 0, 1, '.$ordering.')';
					$db->setQuery($q)->query();
					$val_id = $db->insertid();
					if(!in_array($val_id,$plugin_param['param']['value'])){
						$plugin_param['param']['value'][] = $val_id;
					}
				}
			}
		}
		
		
		$q = 'SELECT id FROM #__virtuemart_product_custom_plg_param_ref WHERE virtuemart_product_id = '.$data['virtuemart_product_id'].' AND virtuemart_custom_id = '.$plugin_param['param']['virtuemart_custom_id'];
		$db->setQuery($q);
		$col_value = empty($plugin_param['param']['intvalue']) ? 'val' : 'intval';
		$exist_values = $db->loadResultArray();
		$ref_values = empty($plugin_param['param']['intvalue']) ? $plugin_param['param']['value'] : array($plugin_param['param']['intvalue']);
		$ref_values = array_diff($ref_values,array('','0',0,null));
		foreach($ref_values as $value){
			if(!empty($exist_values)){
				$ref_id = array_shift($exist_values);
				$q = 'UPDATE #__virtuemart_product_custom_plg_param_ref SET '.$col_value.' = "'.$value.'",virtuemart_product_id = '.$data['virtuemart_product_id'].' WHERE id = '.$ref_id;
				$db->setQuery($q);
				$db->query();
			}else{
				$q = 'INSERT INTO #__virtuemart_product_custom_plg_param_ref ('.$col_value.',virtuemart_product_id,virtuemart_custom_id) VALUES ("'.$value.'",'.$data['virtuemart_product_id'].','.$plugin_param['param']['virtuemart_custom_id'].')';
				$db->setQuery($q);
				$db->query();
			}
		}
		if(!empty($exist_values)){
			$q = 'DELETE FROM `#__virtuemart_product_custom_plg_param_ref` WHERE `id` IN ('.implode(',',$exist_values).')';
			$db->setQuery($q);
			$db->query();
		}
		
		
		// die();
		return $this->OnStoreProduct($data,$plugin_param);
	}

	function plgVmSetOnTablePluginParamsCustom($name, $id, &$table){
		return $this->setOnTablePluginParams($name, $id, $table);
	}

	function plgVmOnDeleteProduct($id, $ok){
		$q  = 'DELETE FROM `#__virtuemart_product_custom_plg_param_ref` WHERE `virtuemart_product_id` = "'.$id.'"';
		$db = JFactory::getDBO();
		$db->setQuery($q);
		$db->query();
		return true;
	}
	
	function plgVmDeclarePluginParamsCustom($psType,$name,$id, &$data){
		return $this->declarePluginParams($psType, $name, $id, $data);
	}

	function plgVmOnDisplayEdit($virtuemart_custom_id,&$customPlugin){
		
		$q = 'SELECT `virtuemart_custom_id`,`custom_title` FROM `#__virtuemart_customs` WHERE `custom_value` = "param"';
		$db =& JFactory::getDBO();
		$db->setQuery($q);
		$customfields = $db->loadObjectList('virtuemart_custom_id');
		$doc = JFactory::getDocument();
		$pluginName = 'param';
		$doc->addStyleSheet(JURI::root().DS.'plugins'.DS.'vmcustom'.DS.'param'.DS.'param'.DS.'assets'.DS.'style.css');
		return $this->onDisplayEditBECustom($virtuemart_custom_id,$customPlugin);
	}
	
	// function onDisplayEditBECustom($virtuemart_custom_id,$customPlugin){
		// return parent::onDisplayEditBECustom($virtuemart_custom_id,$customPlugin);
	// }
	
	// fget_csv analog by Dmitriy Koterov (http://forum.dklab.ru/viewtopic.php?t=9549)
	function fgetcsv($f, $length, $d=",", $q='"') { $list = array(); $st = fgets($f, $length); if ($st === false || $st === null) return $st; while ($st !== "" && $st !== false) { if ($st[0] !== $q) { list ($field) = explode($d, $st, 2); $st = substr($st, strlen($field)+strlen($d)); } else { $st = substr($st, 1); $field = ""; while (1) { preg_match("/^((?:[^$q]+|$q$q)*)/sx", $st, $p); $part = $p[1]; $partlen = strlen($part); $st = substr($st, strlen($p[0])); $field .= str_replace($q.$q, $q, $part); if (strlen($st) && $st[0] === $q) { list ($dummy) = explode($d, $st, 2); $st = substr($st, strlen($dummy)+strlen($d)); break; } else { $st = fgets($f, $length); } } } $list[] = $field; } return $list; } 
	
	function putcsv($list, $d=",", $q='"') { $line = ""; foreach ($list as $field) { $field = str_replace("\r\n", "\n", $field); if(preg_match("/[$d$q\n\r]/", $field)) { $field = $q.str_replace($q, $q.$q, $field).$q; }$line .= $field.$d; }$line = substr($line, 0, -1); $line .= "\n"; return $line; }
	
	function fputcsv($f,$list, $d=",", $q='"'){
		return fputs($f,putcsv($list, $d=",", $q='"'));
	}
	
	
	function plgVmOnCloneProduct($data,$plugin_param){ // not work! need to edit VM2 core
		return $this->OnStoreProduct($data,$plugin_param);
	}
	
	/* redeclare parent functions */
	function getPluginProductDataCustom(&$field,$product_id){

		$id = $this->getIdForCustomIdProduct( $product_id,$field->virtuemart_custom_id) ;

	 	if($id){ // VM2 fix
			$datas = $this->getPluginInternalData($id);
			if($datas){
				foreach($datas as $k=>$v){
					if (!is_string($v) ) continue ;// Only get real Table variable
					if (isset($field->$k) && $v===0) continue ;
					$field->$k = $v;
				}
			}
		}
	}
/*	// VM 2.0.12 fix
	function createPluginTableObject ($tableName, $tableFields, $primaryKey, $tableId, $loggable = FALSE) {

		$db = JFactory::getDBO ();
		// $table = new VmTableData($tableName, $tableId, $db); 
		$table = VmTable::getInstance('PlgVmCustomParam','Table');
		foreach ($tableFields as $field) {
			$table->$field = 0;
		}
		if ($primaryKey !== 0) {
			$table->setPrimaryKey ($primaryKey);
		}
		if ($loggable) {
			$table->setLoggable ();
		}
		if (!$this->_tableChecked) {
			$this->onStoreInstallPluginTable ($this->_psType);
			$this->_tableChecked = TRUE;
		}
		return $table;
	}
	
*/
/*		
	// Cart attribute
	function plgVmOnDisplayProductVariantFE($field,&$idx,&$group) {
		// echo '<pre>'; print_r($group); echo '</pre>';
		// default return if it's not this plugin
		if ($field->custom_element != $this->_name) return '';
		$this->getCustomParams($field);
		// $this->parseCustomParams($group);
		// $this->getPluginProductDataCustom($group, $idx);
		$this->getValues($group->options);
		// echo '<pre>'; print_r($idx); echo '</pre>';
		// echo '<pre>'; print_r($group); echo '</pre>';
			// ob_start();
			// require($this->getLayoutPath('default'));
			// $html = ob_get_clean();

		// echo '<pre>'; print_r($field); echo '</pre>';
		$group->display .= '<div class="product-fields-title">Test!!! cart attribute<br/>';
		$options = reset($group->options);
		foreach($options->cart_values as &$v){
			$group->display .=  '<label><input class="mcf_cart_value" type="checkbox" value="'.$v.'" name="customPrice['.$idx.']['.$group->virtuemart_custom_id.']" /> '.$v.' - <span class="price">'.$field->custom_price.' руб.</span></label><br/>';
		}unset($v);
		$group->display .= '</div>';

		return true;
//         return $html;
    }

	function plgVmOnViewCart($product,$row,&$html) {
		// echo '<pre>'; print_r($row); echo '</pre>';
		// echo '<pre>'; print_r($product); echo '</pre>';
		// if (!$plgParam = $this->GetPluginInCart($product)) return '' ;
		// echo '<pre>'; print_r($plgParam); echo '</pre>';

		$html  .= '<div>';
		if(isset($plgParam) && is_array($plgParam)){
		foreach($plgParam as $k => $item){
			if(!empty($item['comment']) ){
				$html .='<span>'.$item['comment'].'</span>';
			}
		 }
		}
		$html .='</div>';

		return true;
    }
	
	function plgVmOnAddToCart($product){
				echo '<pre>'; print_r($_REQUEST); echo '</pre>';
		foreach($product->customPrices as $k => &$v){
			if(isset($v[$this->virtuemart_custom_id])){
				$options = JRequest::getVar('customPrice',false);
				$options = isset($options[$k][$this->virtuemart_custom_id]) ? $options[$k][$this->virtuemart_custom_id] : false;
				$v[$this->virtuemart_custom_id] = $options;
			}
		}unset($v);
		echo '<pre>'; print_r($product); echo '</pre>';
		die();
	}
	
	function getValues(&$option){
		$db = JFactory::getDBO();
		foreach($option as &$v){
			$q  = 'SELECT `value` FROM `#__virtuemart_product_custom_plg_param` WHERE `virtuemart_custom_id` = "'.$v->virtuemart_custom_id.'" AND `virtuemart_product_id` = "'.$v->virtuemart_product_id.'"';
			$db->setQuery($q);
			$values = $db->loadResult();
			$values = explode('|',$values);
			$values = array_diff($values,array(''));
			$v->cart_values = $values;
		}unset($v);
	}
*/	


// Mike Fix 2012-01-17

	// Cart attribute
	function plgVmOnDisplayProductVariantFE($field,&$idx,&$group) {
		if ($field->custom_element != $this->_name) return '';
		$db = JFactory::getDBO();
		$this->parseCustomParams($field);
		$q  = 'SELECT * FROM `#__virtuemart_product_custom_plg_param_values` as v';
		$q .= ' LEFT JOIN `#__virtuemart_product_custom_plg_param_ref` as r ON v.id = r.val';
		$q .= ' WHERE r.virtuemart_product_id = '.$field->virtuemart_product_id.' AND r.virtuemart_custom_id = '.$field->virtuemart_custom_id;
		$db->setQuery($q);
		$options = $db->loadObjectList();
		$class='';
		$selects= array();
		if(!class_exists('CurrencyDisplay')) require(JPATH_VM_ADMINISTRATOR.DS.'helpers'.DS.'currencydisplay.php');
		$currency = CurrencyDisplay::getInstance();
		foreach ($options as $v) {
			$selects[] = array('value' => $v->value, 'text' => $v->value );
		}
// 		vmdebug('plgVmOnDisplayProductVariantFE',$field,$idx,$group);
		if(!empty($selects)){
			$html = JHTML::_('select.genericlist', $selects,'customPlugin['.$field->virtuemart_customfield_id.']['.$this->_name.'][custom_params]','','value','text',$selects[0],false,true);
			$group->display .= $html;
		}
		return true;
	}


	function plgVmOnAddToCart($product){
		// do some stuff
	}


	function plgVmOnViewCart($product,$row,&$html) {
		if (empty($product->productCustom->custom_element) or $product->productCustom->custom_element != $this->_name) return '';
		if (!$plgParam = $this->GetPluginInCart($product)) return false ;

		$separator= '';
		$html  .= '<span class="custom_param_field">'.$product->productCustom->custom_title.' ';
		foreach ($plgParam as $k => $item) {
			if($product->productCustom->virtuemart_customfield_id==$k){

				if(!empty($item['custom_params']) ){
					$html .=$separator.$item['custom_params'];
					$separator= ',';
				}
			}
		}

		$html .= '</span>';

		return true;
    }


    function plgVmOnViewCartModule( $product,$row,&$html) {
    	return $this->plgVmOnViewCart($product,$row,$html) ;
    }


    /**
     *
     * vendor order display BE
     */
    function plgVmDisplayInOrderBE($item, $row, &$html) {
    	if (empty($item->productCustom->custom_element) or $item->productCustom->custom_element != $this->_name) return '';
    	$this->plgVmOnViewCart($item,$row,$html); //same render as cart
    }

    /**
     *
     * shopper order display FE
     */
    function plgVmDisplayInOrderFE($item, $row, &$html) {
    	if (empty($item->productCustom->custom_element) or $item->productCustom->custom_element != $this->_name) return '';
    	$this->plgVmOnViewCart($item,$row,$html); //same render as cart
    }



	public function plgVmDisplayInOrderCustom(&$html,$item, $param,$productCustom, $row ,$view='FE'){
		$this->plgVmDisplayInOrderCustom($html,$item, $param,$productCustom, $row ,$view);
	}
	
}