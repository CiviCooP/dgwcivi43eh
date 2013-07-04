<?php

require_once 'mutatieproces.civix.php';

/**
 * Implementation of hook_civicrm_config
 */
function mutatieproces_civicrm_config(&$config) {
  _mutatieproces_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 */
function mutatieproces_civicrm_xmlMenu(&$files) {
  _mutatieproces_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 */
function mutatieproces_civicrm_install() {
  return _mutatieproces_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 */
function mutatieproces_civicrm_uninstall() {
  return _mutatieproces_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 */
function mutatieproces_civicrm_enable() {
	_mutatieproces_add_relationship_type('Technisch woonconsulent is', 'Technisch woonconsulent', '', '');
	$dossier = _mutatieproces_add_case('Dossier opzegging huurcontract');
	if ($dossier) {
		_mutatieproces_add_field('einde_huurcontract', 'Opzegging huurcontract', $dossier, 'Case', 'mutatienr', 'Mutatienummer First Noa', 'String', 'Text', '1');
		
		_mutatieproces_add_field('einde_huurcontract', 'Opzegging huurcontract', $dossier, 'Case', 'vge_nr', 'VGE nummer', 'String', 'Text', '1');
		_mutatieproces_add_field('einde_huurcontract', 'Opzegging huurcontract', $dossier, 'Case', 'complexnr', 'Complexnummer', 'String', 'Text', '1');
		_mutatieproces_add_field('einde_huurcontract', 'Opzegging huurcontract', $dossier, 'Case', 'hov_nr', 'Huurovereenkomst nr.', 'String', 'Text', '1');
		_mutatieproces_add_field('einde_huurcontract', 'Opzegging huurcontract', $dossier, 'Case', 'hov_start_datum', 'Huurovereenkomst startdatum', 'Date', 'Select Date', '1');
		
		_mutatieproces_add_field('einde_huurcontract', 'Opzegging huurcontract', $dossier, 'Case', 'vge_straat', 'Straat', 'String', 'Text', '1');
		_mutatieproces_add_field('einde_huurcontract', 'Opzegging huurcontract', $dossier, 'Case', 'vge_huisnummer', 'Huisnummer', 'String', 'Text', '1');
		_mutatieproces_add_field('einde_huurcontract', 'Opzegging huurcontract', $dossier, 'Case', 'vge_suffix', 'Toevoeging', 'String', 'Text', '1');
		_mutatieproces_add_field('einde_huurcontract', 'Opzegging huurcontract', $dossier, 'Case', 'vge_postcode', 'Postcode', 'String', 'Text', '1');
		_mutatieproces_add_field('einde_huurcontract', 'Opzegging huurcontract', $dossier, 'Case', 'vge_plaats', 'Woonplaats', 'String', 'Text', '1');
		
		_mutatieproces_add_field('einde_huurcontract', 'Opzegging huurcontract', $dossier, 'Case', 'hov_end_datum', 'Verwachte einddatum', 'Date', 'Select Date', '1');
		
		_mutatieproces_add_field('einde_huurcontract', 'Opzegging huurcontract', $dossier, 'Case', 'woningwaardering', 'Woningwaardering', 'Memo', 'TextArea', '1');
		_mutatieproces_add_field('einde_huurcontract', 'Opzegging huurcontract', $dossier, 'Case', 'woningoppervlakte', 'Totale woonoppervlakte', 'String', 'Text', '1');
		
		_mutatieproces_add_field('einde_huurcontract', 'Opzegging huurcontract', $dossier, 'Case', 'epa_label', 'EPA label', 'String', 'Text', '1');
		_mutatieproces_add_field('einde_huurcontract', 'Opzegging huurcontract', $dossier, 'Case', 'epa_pre_label', 'EPA pre-label', 'String', 'Text', '1');
		
		_mutatieproces_add_field('einde_huurcontract', 'Opzegging huurcontract', $dossier, 'Case', 'plattegrond', 'Bijlage plattegrond', 'File', 'File', '1');
		_mutatieproces_add_field('einde_huurcontract', 'Opzegging huurcontract', $dossier, 'Case', 'opnamerapport', 'Bijlage opnamerapport', 'File', 'File', '1');
		_mutatieproces_add_field('einde_huurcontract', 'Opzegging huurcontract', $dossier, 'Case', 'staat_van_oplevering', 'Bijlage staat van oplevering', 'File', 'File', '1');
	}
	
	_mutatieproces_enable_group('einde_huurcontract');
	return _mutatieproces_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 */
function mutatieproces_civicrm_disable() {
	_mutatieproces_delete_group('einde_huurcontract');
	return _mutatieproces_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 */
function mutatieproces_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _mutatieproces_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 */
function mutatieproces_civicrm_managed(&$entities) {
  return _mutatieproces_civix_civicrm_managed($entities);
}

function _mutatieproces_add_activity_type($type, $description) {
	$componentCase = 7; //activity type for civi case
	$param = array(
		"label"=>$type,
		"description"=> $description,
		"component_id" => $componentCase, 
		"is_reserved"=>false,
		"is_active"=>1,
		"weight"=>1,
		"version"=>3
	);
	$result = civicrm_api('activity_type', 'create', $param);
}

function _mutatieproces_add_relationship_type($name_a_b, $name_b_a, $contact_type_a, $contact_type_b) {
   $params['name_a_b'] = $name_a_b;
   $params['name_b_a'] = $name_b_a;
   $params['contact_type_a'] = $contact_type_a;
   $params['contact_type_b'] = $contact_type_b;   
   $params['version'] = 3;
   $result = civicrm_api('relationship_type', 'get', $params);
   if ($result['is_error'] == 1 || $result['count'] == 0) {
		$result = civicrm_api('relationship_type', 'create', $params);
   }
}

function _mutatieproces_add_case($case) {
	$option_group = civicrm_api('OptionGroup', 'getsingle', array('name' => 'case_type', 'version' => '3'));
	$option_group_id = false;
	if (isset($option_group['id'])) {
		$option_group_id = $option_group['id'];
	}
	if (!$option_group_id) {
		return false;
	}
	
	$option_value = civicrm_api('OptionValue', 'getsingle', array('option_group_id' => $option_group_id, 'name' => $case, 'version' => '3'));
	$option_value_id = false;
	$option_value_value = false;
	if (isset($option_value['id'])) {
		$option_value_id = $option_value['id'];
		$option_value_value = $option_value['value'];
	}
	if (!$option_value_id) {
		$option_value = civicrm_api('OptionValue', 'create', array('option_group_id' => $option_group_id, 'name' => $case, 'version' => '3'));
		if (isset($option_value['id']) && isset($option_value['values'][0])) {
			$option_value_id = $option_value['id'];
			$option_value_value = $option_value['values'][0]['value'];
		}
	}
	
	return $option_value_value;
}

function _mutatieproces_add_field($group, $group_title, $case_id, $extends, $name, $label, $data_type, $html_type, $active) {
	$params['version']  = 3;
	$params['name'] = $group;
	$result = civicrm_api('CustomGroup', 'getsingle', $params);
	if (!isset($result['id'])) {
		unset($params);
		$params['version']  = 3;
		$params['name'] = $group;
		$params['title'] = $group_title;
		$params['extends'] = $extends;
		$params['extends_entity_column_value'] = $case_id;
		$params['is_active'] = '1';
		$result = civicrm_api('CustomGroup', 'create', $params);
	}
	$gid = false;
	if (isset($result['id'])) {
		$gid = $result['id'];
	}
	
	if ($gid) {
		unset($params);
		$params['version']  = 3;
		$params['custom_group_id'] = $gid;
		$params['label'] = $label;
		$result = civicrm_api('CustomField', 'getsingle', $params);
		if (!isset($result['id'])) {
			unset($params);
			$params['version']  = 3;
			$params['custom_group_id'] = $gid;
			$params['name'] = $name;
			$params['label'] = $label;
			$params['html_type'] = $html_type;
			$params['data_type'] = $data_type;
			$params['is_active'] = $active;
			$result = civicrm_api('CustomField', 'create', $params);
		}
	}
}

function _mutatieproces_delete_group($name) {
	$params['version']  = 3;
	$params['name'] = $name;
	$result = civicrm_api('CustomGroup', 'getsingle', $params);
	if (isset($result['id'])) {
		$gid = $result['id'];
		unset($params);
		$params['version']  = 3;
		$params['custom_group_id'] = $gid;
		$result = civicrm_api('CustomField', 'get', $params);
		if (isset($result['values']) && is_array($result['values'])) {
			foreach($result['values']  as $field) {
				unset($params);
				$params['version']  = 3;
				$params['id'] = $field['id'];
				civicrm_api('CustomField', 'delete', $params);
			}
		}
	
		unset($params);
		$params['version']  = 3;
		$params['id'] = $gid;
		$result = civicrm_api('CustomGroup', 'delete', $params);
	}
}

function _mutatieproces_enable_group($name, $enable) {
  $params['version']  = 3;
  $params['name'] = $name;
  $result = civicrm_api('CustomGroup', 'getsingle', $params);
  if (isset($result['id'])) {
	$gid = $result['id'];
	unset($params);
	$params['version']  = 3;
	$params['id'] = $gid;
	$params['is_active'] = $enable ? '1' : '0';
	$result = civicrm_api('CustomGroup', 'update', $params);
  }
}
