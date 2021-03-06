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
	_mutatieproces_add_activity_type('Adviesgesprek', 'Inplannen van een adviesgesprek');
	$dossier = _mutatieproces_add_case('DossierOpzeggingHuurcontract');
	$gid = false;
	if ($dossier) {
		$gid = mutatieproces_add_group('einde_huurcontract', 'Opzegging huurcontract', $dossier, 'Case');
	}

	if ($gid) {
		_mutatieproces_add_field($gid, 'mutatienr', 'Mutatienummer First Noa', 'String', 'Text', '1', 1);

		_mutatieproces_add_field($gid, 'vge_nr', 'VGE nummer', 'String', 'Text', '1', 2);
		_mutatieproces_add_field($gid, 'complexnr', 'Complexnummer', 'String', 'Text', '1', 3);
		_mutatieproces_add_field($gid, 'hov_nr', 'Huurovereenkomst nr.', 'String', 'Text', '1', 4);
		_mutatieproces_add_field($gid, 'hov_start_datum', 'Huurovereenkomst startdatum', 'Date', 'Select Date', '1', 5);

		_mutatieproces_add_field($gid, 'hoofdhuurder_first', 'Persoonsnummer First (hoofdhuurder)', 'String', 'Text', '1', 6);
		_mutatieproces_add_field($gid, 'medehuurder_first', 'Persoonsnummer First (medehuurder)', 'String', 'Text', '1', 7);

		_mutatieproces_add_field($gid, 'vge_adres', 'VGE Adres', 'String', 'Text', '1', 8);
		_mutatieproces_add_field($gid, 'vge_straat', 'Straat', 'String', 'Text', '1', 9);
		_mutatieproces_add_field($gid, 'vge_huisnummer', 'Huisnummer', 'String', 'Text', '1', 10);
		_mutatieproces_add_field($gid, 'vge_suffix', 'Toevoeging', 'String', 'Text', '1', 11);
		_mutatieproces_add_field($gid, 'vge_postcode', 'Postcode', 'String', 'Text', '1', 12);
		_mutatieproces_add_field($gid, 'vge_plaats', 'Woonplaats', 'String', 'Text', '1', 13);

		_mutatieproces_add_field($gid, 'verwachte_eind_datum', 'Verwachte einddatum', 'Date', 'Select Date', '1', 14);

		_mutatieproces_add_field($gid, 'woningwaardering', 'Woningwaardering', 'Memo', 'TextArea', '1', 15);
		_mutatieproces_add_field($gid, 'woningoppervlakte', 'Totale woonoppervlakte', 'String', 'Text', '1', 16);

		_mutatieproces_add_field($gid, 'epa_label', 'EPA label', 'String', 'Text', '1', 17);
		_mutatieproces_add_field($gid, 'epa_pre_label', 'EPA pre-label', 'String', 'Text', '1', 18);

		_mutatieproces_add_field($gid, 'plattegrond', 'Bijlage plattegrond', 'File', 'File', '1', 19);
		_mutatieproces_add_field($gid, 'opnamerapport', 'Bijlage opnamerapport', 'File', 'File', '1', 20);
		_mutatieproces_add_field($gid, 'staat_van_oplevering', 'Bijlage staat van oplevering', 'File', 'File', '1', 21);

		_mutatieproces_enable_group('einde_huurcontract', true);
	}

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
   if (strlen($contact_type_a)) {
   		$params['contact_type_a'] = $contact_type_a;
   }
   if (strlen($contact_type_b)) {
   		$params['contact_type_b'] = $contact_type_b;
   }
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
		if (isset($option_value['id']) && is_array($option_value['values']) && count($option_value['values'])) {
			$v = reset($option_value['values']);
			$option_value_id = $option_value['id'];
			$option_value_value = $v['value'];
		}
	}

	return $option_value_value;
}

function mutatieproces_add_group($group, $group_title, $case_id, $extends) {
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

	return $gid;
}

function _mutatieproces_add_field($gid, $name, $label, $data_type, $html_type, $active, $weight = 0) {
	$params['version']  = 3;
	$params['custom_group_id'] = $gid;
	$params['label'] = $label;
	$result = civicrm_api('CustomField', 'getsingle', $params);
	if (!isset($result['id'])) {
		unset($params);
		$params['version']  = 3;
		$params['custom_group_id'] = $gid;
		$params['name'] = $name;
		$params['label'] = $name;
		$params['html_type'] = $html_type;
		$params['data_type'] = $data_type;
		$params['is_active'] = $active;
		$params['weight'] = $weight;
		$result = civicrm_api('CustomField', 'create', $params);

		$params2['version'] = 3;
		$params2['label'] = $label;
		$params2['is_active'] = $active;
		$params2['id'] = $result['id'];

		civicrm_api('CustomField', 'create', $params2);
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


function mutatieproces_civicrm_pageRun( &$page ) {

	$hov_opzeggen = false;
	$huishouden_id = $page->getVar('_contactId');
	$contactId = $page->getVar('_contactId');

	$contactHoofdHuurder = CRM_Utils_DgwUtils::checkContactHoofdhuurder( $contactId );
	if ( $contactHoofdHuurder ) {
		$huishoudens = CRM_Utils_DgwUtils::getHuishoudens( $contactId, 'relatie hoofdhuurder', true );
		foreach($huishoudens as $huishouden) {
			$huishouden_id = $huishouden['huishouden_id'];
		}
	}

	$result = civicrm_api('Contact', 'getsingle', array('version' => 3, 'contact_id' => $huishouden_id));
	if (!isset($result['is_error'])) {
		if ($result['contact_type'] == 'Household') {
			$civiparms2 = array('version' => 3, 'name' => 'HOV_nummer_First');
			$civires2 = civicrm_api('CustomField', 'getsingle', $civiparms2);
			if (!civicrm_error($civires2)) {
				$custom_id = $civires2['id'];
				$result = civicrm_api('Contact', 'getsingle', array('version' => 3, 'contact_id' => $huishouden_id, 'return.custom_'.$custom_id => 1));
				if (isset($result['custom_'.$custom_id]) && $result['custom_'.$custom_id]) {
					$hov_opzeggen = true;
				}
			}
		}
	}

	if ($hov_opzeggen) {
		$page->assign('show_hov_opzeggen', '1');
		$page->assign('hov_opzeggen_contact_id', $huishouden_id);
	} else {
		$page->assign('show_hov_opzeggen', '0');
		$page->assign('hov_opzeggen_contact_id', '0');
	}
}

function mutatieproces_civicrm_buildForm($formName, &$form) {
	if ($formName == 'CRM_Case_Form_Case') {
		if ($form->getAction() == CRM_Core_Action::ADD) {
			$case_type_id = _mutatieproces_get_case_type_id('DossierOpzeggingHuurcontract');
			if ($case_type_id && $form->elementExists('case_type_id')) {
				$cases = $form->getElement('case_type_id');
				foreach($cases->_options as $id => $option) {
					if ($id == $case_type_id) {
						unset($cases->_options[$id]);
					}
				}
			}
		}
	}
}

function _mutatieproces_get_case_type_id($case) {
	$option_group = civicrm_api('OptionGroup', 'getsingle', array('name' => 'case_type', 'version' => '3'));
	$option_group_id = false;
	if (isset($option_group['id'])) {
		$option_group_id = $option_group['id'];
	} else {
		return false;
	}
	$option_value = civicrm_api('OptionValue', 'getsingle', array('option_group_id' => $option_group_id, 'name' => $case, 'version' => '3'));
	$option_value_id = false;
	$option_value_value = false;
	if (isset($option_value['id'])) {
		$option_value_id = $option_value['id'];
		$option_value_value = $option_value['value'];
	}

	return $option_value_value;
}