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
	
	public static function retrieveCustomValuesForContact($data) {
		$return['is_error'] = '0';
		if (!isset($data['contact_id']) || !isset($data['contact_type'])) {
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