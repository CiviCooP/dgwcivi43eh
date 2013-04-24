<?php

/*
 +--------------------------------------------------------------------+
| CiviCRM version 4.3                                                |
+--------------------------------------------------------------------+
| Copyright CiviCoop Academic Free License v3.02013                  |
+--------------------------------------------------------------------+
*/

/*
 * Function to remove contact from group FirstSync
 */
function civicrm_api3_dgw_firstsync_remove($inparms) {
	/** 
     * @ToDo fetch id's by customfieldgroup name
	 */
	$group_id = 8;
	$error_group_id = 6;
	
	/*
	 * if contact_id empty or not numeric, error
	*/
	if (!isset($inparms['contact_id'])) {
		return civicrm_api3_create_error("Geen contact_id in parms in dgwcontact_firstsyncremove");
	} else {
		$contact_id = trim($inparms['contact_id']);
	}

	if (empty($contact_id)) {
		return civicrm_api3_create_error( "Leeg contact_id voor dgwcontact_firstsyncremove" );
	}

	if (!is_numeric($contact_id)) {
		return civicrm_api3_create_error( "Contact_id '.$contact_id.' heeft niet numerieke waarden in dgwcontact_firstsyncremove");
	}

	/*
	 * if action empty or not "ins", "del" or "upd", error
	*/
	if (!isset($inparms['dgwaction'])) {
		return civicrm_api3_create_error("Geen action in parms in dgwcontact_firstsyncremove");
	} else {
		$action = trim(strtolower($inparms['dgwaction']));
	}

	if (empty($action)) {
		return civicrm_api3_create_error("Lege action voor dgwcontact_firstsyncremove");
	}
	if ($action != "ins" && $action != "upd" && $action != "del") {
		return civicrm_api3_create_error("Ongeldige waarde ".$action. " voor action in dgwcontact_firstsyncremove");
	}

	/*
	 * if entity empty or invalid, error
	*/
	if (!isset($inparms['dgwentity'])) {
		return civicrm_api3_create_error("Geen entity in parms in dgwcontact_firstsyncremove");
	} else {
		$entity = trim(strtolower($inparms['dgwentity']));
	}

	if (empty($entity)) {
		return civicrm_api3_create_error("Lege entity voor dgwcontact_firstsyncremove");
	}

	if ($entity != "contact" && $entity != "phone" && $entity != "email" && $entity != "address") {
		return civicrm_api3_create_error("Ongeldige waarde ".$entity." voor entity in dgwcontact_firstsyncremove");
	}

	/*
	 * entity_id or key_first required
	*/
	if (!isset($inparms['entity_id']) && !isset($inparms['key_first'])) {
		return civicrm_api3_create_error("Entity_id en key_first ontbreken in dgwcontact_firstsyncremove");
	}

	if (empty($inparms['entity_id']) && empty($inparms['key_first'])) {
		return civicrm_api3_create_error("Entity_id en key_first zijn beiden leeg in dgwcontact_firstsyncremove");
	}

	if (isset($inparms['entity_id'])) {
		$entity_id = trim($inparms['entity_id']);
		if (!is_numeric($entity_id)) {
			return civicrm_api3_create_error("Entity_id kan alleen numeriek zijn, doorgegeven was $entity_id");
		}
	} else {
		$entity_id = null;
	}
	if (isset($inparms['key_first'])) {
		$key_first = trim($inparms['key_first']);
		if (!is_numeric($key_first)) {
			return civicrm_api3_create_error("Key_first kan alleen numeriek zijn, doorgegeven was $key_first");
		}
	} else {
		$key_first = null;
	}
	
	$custom_group = CRM_Utils_DgwApiUtils::retrieveCustomGroupByid($group_id);
	$custom_fields = CRM_Utils_DgwApiUtils::retrieveCustomValuesForContactAndCustomGroupSorted( $contact_id, $group_id);
	$action_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('action');
	if (!is_array($action_field)) {
		return civicrm_api3_create_error('invalid custom field action');
	}
	
	/*
	 * issue 86 : check if contact_id exists, only process if it does
	*/
	$checkQry = "SELECT * FROM ".$custom_group['table_name']." WHERE entity_id = $contact_id";
	$checkSync = CRM_Core_DAO::executeQuery( $checkQry );
	if ($checkSync->fetch()) {
		/*
		* remove entry from firstsync error table with incoming parms,
		* delete from synctable if action is 'del' and set action to none for all
		* others
		*/
		if (!empty($entity_id)) {
			if ($action == "del") {
				$fields = array(
					'entity' => $entity,
					'action' => $action,
				);
				CRM_Utils_DgwApiUtils::removeCustomValuesRecord($group_id, $entity_id, $fields);
			} else {
				foreach($custom_fields as $id => $field) {
					if ($field['action'] != 'del' && $field['entity'] == $entity && $field['entity_id'] == $entity_id) {
						$civiparms2 = array(
							'version' => 3,
							'entity_id' => $contact_id,
							'custom_'.$action_field['id'].':'.$id => 'none',
						);
						$civicres2 = civicrm_api('CustomValue', 'Create', $civiparms2);
						print_r($civicres2); exit();
						if (civicrm_error($civires2)) {
							return civicrm_api3_create_error($civires2['error_message']);
						}
					} 
				}
			}
			
			$fields = array(
				'action_err' => $action,
				'entity_err' => $entity,
				'entity_id_err' => $entity_id,
			);
			CRM_Utils_DgwApiUtils::removeCustomValuesRecord($error_group_id, $contact_id, $fields);
		} else {
			if ($action == "del") {
				$fields = array(
					'entity' => $entity,
					'action' => $action,
					'key_first' => $key_first,
				);
				CRM_Utils_DgwApiUtils::removeCustomValuesRecord($group_id, $entity_id, $fields);
			} else {
				foreach($custom_fields as $id => $field) {
					if ($field['action'] != 'del' && $field['entity'] == $entity && $field['entity_id'] == $contact_id && $field['key_first'] == $key_first) {
						$civiparms2 = array(
								'version' => 3,
								'entity_id' => $contact_id,
								'custom_'.$action_field['id'].':'.$id => 'none',
						);
						$civicres2 = civicrm_api('CustomValue', 'Create', $civiparms2);
						if (civicrm_error($civires2)) {
							return civicrm_api3_create_error($civires2['error_message']);
						}
					}
				}
			}
			
			$fields = array(
					'action_err' => $action,
					'entity_err' => $entity,
					'key_first_err' => $key_first,
			);
			CRM_Utils_DgwApiUtils::removeCustomValuesRecord($error_group_id, $contact_id, $fields);
		}

		/*
		* if no entries left in synctable for contact with action
		* upd, action del or action ins, remove contact from group firstsync
		*/
		$fields = CRM_Utils_DgwApiUtils::retrieveCustomValuesForContactAndCustomGroupSorted( $contact_id, $group_id);
		$aantal = 0;
		foreach($fields as $field) {
			if ($field['action'] == 'ins' || $field['action'] == 'upd' || $field['action'] == 'del') {
				$aantal ++;
			}
		}
		
		if ($aantal == 0) {
			$gid = CRM_Utils_DgwApiUtils::getGroupIdByTitle('FirstSync');
			$civiparms2 = array(
					"version"       => 3, 
					"contact_id"    =>  $contact_id,
					"group_id"      =>  $gid);
			$civires2 = civicrm_api('GroupContact', 'delete', $civiparms2);
			if (civicrm_error($civires2)) {
				return civicrm_api3_create_error($civires2['error_message']);
			}
		}
		return "Firstsync remove processed correctly";
	} else {
		return "No sync records found for contact_id";
	}
}

/*
 * Function to sync with first
*/
function civicrm_api3_dgw_firstsync_get($inparms) {

	/**
     * @Todo upgrade id with api call on name
	 */
	$group_for_first_sync = 8;
	
	/*
	 * initialize output parameter array
	*/
	$outparms = array("");
	$civiparms = array (
			'version' => 3,
	);
	
	$group_id = CRM_Utils_DgwApiUtils::getGroupIdByTitle('FirstSync');
	if ($group_id === false) {
		return civicrm_api3_create_error('No group FirstSync found');
	}
	

	/**
	 * Use the GroupContact api
	 */
	$civiparms['group_id'] = $group_id;
	$civires1 = civicrm_api('GroupContact', 'get', $civiparms);
	if (civicrm_error($civires1)) {
		return civicrm_api3_create_error($civires1['error_message']);
	}

	$i = 1;
	foreach ($civires1['values'] as $contact) {
		
		$pers_first = CRM_Utils_DgwApiUtils::retrievePersoonsNummerFirst($contact['contact_id']);
		$fields = CRM_Utils_DgwApiUtils::retrieveCustomValuesForContactAndCustomGroupSorted( $contact['contact_id'], $group_for_first_sync);
		
		foreach($fields as $field) {
			$proccessRecord = true;
			/*
			 * issue 269: do not send if key_first is empty and action is
			* not ins
			* do not send if entity = address or contact and action is
			* delete
			*/
			if (empty($field['key_first']) && $field['action'] != 'ins') {
				$proccessRecord = false;
			}
			if ($field['action'] == 'del' && ($field['entity'] == 'contact' || $field['entity'] == 'address')) {
				$proccessRecord = false;
			} 
			if ($field['action'] == 'none') {
				$proccessRecord = false;
			}
			if ($proccessRecord) {
				$data = $field;
				$data['contact_id'] = $contact['contact_id'];
				$data['persoonsnummer_first'] = $pers_first;
				$outparms[$i] = $data;
				$i++;
			}
		}
	}
	$outparms[0]['record_count'] = $i - 1;
	return $outparms;
}
