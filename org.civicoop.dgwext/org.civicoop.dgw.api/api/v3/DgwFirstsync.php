<?php

/*
 +--------------------------------------------------------------------+
| CiviCRM version 4.3                                                |
+--------------------------------------------------------------------+
| Copyright CiviCoop Academic Free License v3.02013                  |
+--------------------------------------------------------------------+
*/

/*
 * Function to sync with first
*/
function civicrm_api3_dgw_firstsync_get($inparms) {

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
		$customValues = CRM_Utils_DgwApiUtils::retrieveCustomValuesForContactAndCustomGroup( $contact['contact_id'], $group_for_first_sync);
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
