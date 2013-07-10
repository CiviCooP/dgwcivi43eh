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
class CRM_Utils_DgwMutatieprocesUtils {

	public static function retrieveCustomGroupByName($name) {
		$civiparms2 = array('version' => 3, 'name' => $name);
		$civires2 = civicrm_api('CustomGroup', 'getsingle', $civiparms2);
		$id = false;
		if (!civicrm_error($civires2)) {
			return $civires2;
		}
		return false;
	}

	public static function retrieveCustomValuesForContactAndCustomGroupSorted($contact_id, $group_id) {
		$customValues = CRM_Utils_DgwMutatieprocesUtils::retrieveCustomValuesForContactAndCustomGroup($contact_id, $group_id);
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
	
	public static function getPersoonsnummerFirstByRelation($huishouden_id, $relation) {
		$return = false;
		$cid = false;
		$result = civicrm_api('RelationshipType', 'getsingle', array('version'=>3, 'label_b_a'=>$relation));
		if (isset($result['id']) && $result['id']) {
			$result = civicrm_api('Relationship', 'getsingle', array('version'=>3, 'contact_id_b'=>$huishouden_id, 'relationship_type_id' => $result['id']));
			if (isset($result['id']) && $result['id']) {
				$cid = $result['contact_id_a'];
			}	
		}
		
		if ($cid) {
			$pers_nr_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('Persoonsnummer_First');
			$params['version'] =3;
			$params['contact_id'] =$cid;
			$params['return.custom_'.$pers_nr_field['id']] = 1;
			$result = civicrm_api('Contact', 'getsingle', $params);
			if (isset($result['id']) && $result['id']) {
				return $result['custom_'.$pers_nr_field['id']];
			}
		}
		
		return false;
	}
	
	public static function getContactIdByRelation($huishouden_id, $relation) {
		$return = false;
		$cid = false;
		$result = civicrm_api('RelationshipType', 'getsingle', array('version'=>3, 'label_b_a'=>$relation));
		if (isset($result['id']) && $result['id']) {
			$result = civicrm_api('Relationship', 'getsingle', array('version'=>3, 'contact_id_b'=>$huishouden_id, 'relationship_type_id' => $result['id']));
			if (isset($result['id']) && $result['id']) {
				$cid = $result['contact_id_a'];
			}
		}
		
		return $cid;
	}
	
	public static function retrieveCustomFieldByName($name, $gid=false) {
		$civiparms2 = array('version' => 3, 'name' => $name);
		if ($gid) {
			$civiparms2['custom_group_id'] = $gid;
		}
		$civires2 = civicrm_api('CustomField', 'getsingle', $civiparms2);
		if (!civicrm_error($civires2)) {
			return $civires2;
		}
		return false;
	}
	
	public static function createTag($name) {
		$result = civicrm_api('Tag', 'getsingle', array('name' => $name, 'version' => 3));
		if (isset($result['id']) && $result['id']) {
			return $result['id'];
		}
		$params = array(
			'version' => 3,
			'name' => $name
		);
		
		$result = civicrm_api('Tag', 'Create', $params);
		if (isset($result['id']) && $result['id']) {
			return $result['id'];
		}
		return false;
	}
	
	public static function addTag($tag_id, $contact_id) {
		$params = array(
			'version' => 3,
			'contact_id' => $contact_id,
			'tag_id' => $tag_id
		);
		return civicrm_api('EntityTag', 'Create', $params);
	}

}
