<?php
/*
+--------------------------------------------------------------------+
| Project       :   CiviCRM De Goede Woning - Upgrade CiviCRM 4.3    |
| Author        :   Jaap Jansma (CiviCooP, jaap.jansma@civicoop.org  |
| Date          :   16 April 20134                                   |
| Description   :   Class with DGW helper functions for the          |
|                   custom DGWApi                                    |
+--------------------------------------------------------------------+
*/

/**
*
* @package CRM
* @copyright CiviCRM LLC (c) 2004-2013
* $Id$
*
*/
class CRM_Utils_DgwApiUtils {
	
	public static function parseEntity($action) {
		$entities = array(
				'phone' => 'DgwPhone',
				'note' => 'DgwNote',
				'email' => 'DgwEmail',
				'address' => 'DgwAddress',
				'group' => 'DgwGroup',
				'tag' => 'DgwTag',
				'relationship' => 'DgwRelationship',
				'firstsync' => 'DgwFirstsync',
		);
		
		/*$actions = array(
			'remove' => 'delete'
		);*/
		
		$return['entity'] = 'Dgwcontact';
		$return['action'] = $action;
		
		foreach($entities as $key => $value) {
			if (strpos($action, $key) === 0) {
				$return['entity'] = $value;
				$return['action'] = str_replace($key, "", $action);
			}
		}
		/*foreach($actions as $key => $value) {
			if ($return['action'] == $key) {
				$return['action'] = $value;
			}
		}*/
		
		return $return;
	}
	
	public static function getLocationByid($id) {
		$civiparms2 = array('version' => 3, 'id' => $id);
		$civires2 = civicrm_api('LocationType', 'getsingle', $civiparms2);
		$locationType = "";
		if (!civicrm_error($civires2)) {
			$locationType = $civires2['name'];
		}
		return $locationType;
	}
	
	public static function getLocationIdByName($name) {
		$civiparms2 = array('version' => 3, 'name' => $name);
		$civires2 = civicrm_api('LocationType', 'getsingle', $civiparms2);
		$locationType = "";
		if (!civicrm_error($civires2)) {
			$locationType = $civires2['id'];
		}
		return $locationType;
	}
	
	public static function getGroupIdByTitle($title) {
		$civiparms2 = array('version' => 3, 'title' => $title);
		$civires2 = civicrm_api('Group', 'getsingle', $civiparms2);
		$id = false;
		if (!civicrm_error($civires2)) {
			$id = $civires2['id'];
		}
		return $id;
	}
	
	/*
	 * function to check if a contact is a hoofdhuurder
	*/
	public static function is_hoofdhuurder($contact_id) {
		/*
		 * only if contact_id is not empty
		*/
		if (empty($contact_id)) {
			return 0;
		}
		/*
		 * check if there is a relationship 'hoofdhuurder' for the contact_id
		*/
		$rel_hfd_id = self::retrieveRelationshipTypeIdByNameAB('Hoofdhuurder');
		$parms = array(
			'version' => 3,
			'relationship_type_id' => $rel_hfd_id,
			'contact_id_a' => $contact_id,
		);
		$res = civicrm_api('Relationship', 'get', $parms);
		if (is_array($res['values'])) {
			$c = reset($res['values']);
			return $c['contact_id_b'];
		}
		return 0;
	}
	
	public static function retrieveRelationshipTypeIdByNameAB($name) {
		$id = 0;
		$parms = array(
			'version' => 3,
			'name_a_b' => $name
		);
		$res = civicrm_api('RelationshipType', 'getsingle', $parms);
		if (!civicrm_error($res)) {
			$id = $res['id'];
		}
		return $id;
	}
	
	public static function retrieveCustomGroupByid($group_id) {
		$civiparms2 = array('version' => 3, 'id' => $group_id);
		$civires2 = civicrm_api('CustomGroup', 'getsingle', $civiparms2);
		$id = false;
		if (!civicrm_error($civires2)) {
			return $civires2;
		}
		return false;
	}
	
	public static function retrieveCustomGroupByName($name) {
		$civiparms2 = array('version' => 3, 'name' => $name);
		$civires2 = civicrm_api('CustomGroup', 'getsingle', $civiparms2);
		$id = false;
		if (!civicrm_error($civires2)) {
			return $civires2;
		}
		return false;
	}
	
	public static function retrieveCustomFieldByName($name) {
		$civiparms2 = array('version' => 3, 'name' => $name);
		$civires2 = civicrm_api('CustomField', 'getsingle', $civiparms2);
		$id = false;
		if (!civicrm_error($civires2)) {
			return $civires2;
		}
		return false;
	}
	
	public static function removeCustomValuesRecord($group_id, $entity_id, $fields) {
		$custom_group = CRM_Utils_DgwApiUtils::retrieveCustomGroupByid($group_id);
		$where = "";
		foreach($fields as $key => $value) {
			$custom_field = self::retrieveCustomFieldByName($key);
			if (is_array($custom_field)) {
				$field_name = $custom_field['column_name'];
				$where .= " AND `".$field_name."` = '".mysql_escape_string($value)."'";
			}
		}
		if (strlen($where)) {
			$qry1 = "DELETE FROM ".$custom_group['table_name']." WHERE entity_id = ".$entity_id.$where;
			CRM_Core_DAO::executeQuery($qry1);
		}
	}
	
	public static function retrieveCustomValuesForContactAndCustomGroupSorted($contact_id, $group_id) {
		$customValues = CRM_Utils_DgwApiUtils::retrieveCustomValuesForContactAndCustomGroup($contact_id, $group_id);
		$fields = array();
		if (isset($customValues['values']) && is_array($customValues['values'])) {
			foreach($customValues['values'] as $values) {
				foreach($values as $key => $v) {
					if ($key != 'entity_id' && $key != 'id' && $key != 'latest' && $key != 'name') {
						$fields[$key][$values['name']] = $v;
					} 
				}
			}
		}
		return $fields;
	}
	
	public static function retrieveCustomValuesForContactAndCustomGroup($contact_id, $group_id) {
		$data['contact_id'] = $contact_id;
		$return['is_error'] = '0';
		if (!isset($data['contact_id'])) {
			$return['is_error'] = '1';
			$return['error_message'] ='Invalid input parameters expected contact_id and contact_type';
			return $return;
		}
	
		$params = array(
				'version' => 3,
				'sequential' => 1,
				'entity_id' => $data['contact_id'],
				'onlyActiveFields' => '0',
		);
		$values = civicrm_api('CustomValue', 'get', $params);
		if (isset($values['is_error']) && $values['is_error'] == '1') {
			return $values;
		}
		
		$i = 0;
		foreach($values['values'] as $value) {
			$params = array(
					'version' => 3,
					'sequential' => 1,
					'id' => $value['id'],
					'custom_group_id' => $group_id
			);
			$fields = civicrm_api('CustomField', 'getsingle', $params);
			if (!isset($fields['is_error'])) {
				$return['values'][$i] = $value;
				$return['values'][$i]['name'] = $fields['name'];
				$i++;					
			}
		}
		return $return;
	}
	
	public static function retrievePersoonsNummerFirst($contact_id) {
		$pers_first = "onbekend";
		$return = self::retrieveCustomValuesForContact(array('contact_id' => $contact_id));
		if (isset($return['values']) && isset($return['values']['Persoonsnummer_First']) && isset($return['values']['Persoonsnummer_First']['value']) && strlen(isset($return['values']['Persoonsnummer_First']['value'])) ) {
			$pers_first = $return['values']['Persoonsnummer_First']['value'];
		}
		return $pers_first;
	}
	
	public static function retrieveCustomValuesForContact($data) {
		$return['is_error'] = '0';
		if (!isset($data['contact_id'])) {
			$return['is_error'] = '1';
			$return['error_message'] ='Invalid input parameters expected contact_id and contact_type';
			return $return;
		}
		
		$params = array(
				'version' => 3,
				'sequential' => 1,
				'entity_id' => $data['contact_id']
		);
		$values = civicrm_api('CustomValue', 'get', $params);
		if (isset($values['is_error']) && $values['is_error'] == '1') {
			return $values;
		}
		foreach($values['values'] as $value) {
			$params = array(
				'version' => 3,
				'sequential' => 1,
				'id' => $value['id']
			);
			$fields = civicrm_api('CustomField', 'getsingle', $params);
			if (!isset($fields['is_error'])) {
				$name = $fields['name'];
				$return['values'][$name]['name'] = $name;
				$return['values'][$name]['value'] = $value['latest'];
				if (isset($fields['option_group_id'])) {
					$params = array(
						'version' => 3,
						'sequential' => 1,
						'option_group_id' => $fields['option_group_id'],
						'value' => $return['values'][$name]['value']
					);
					$options = civicrm_api('OptionValue', 'getsingle', $params);
					if (!isset($options['is_error'])) {
						$return['values'][$name]['normalized_value'] = $options['label'];
					}
				}
					
			}
		}
		return $return;
	}
}