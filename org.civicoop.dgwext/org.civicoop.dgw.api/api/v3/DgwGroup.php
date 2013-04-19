<?php

/*
+--------------------------------------------------------------------+
| CiviCRM version 4.3                                                |
+--------------------------------------------------------------------+
| Copyright CiviCoop Academic Free License v3.02013                  |
+--------------------------------------------------------------------+
*/

/*
 * Function to get phones for a contact
*/
function civicrm_api3_dgw_group_get($inparms) {

	/*
	 * initialize output parameter array
	*/
	$outparms = array("");
	$civiparms = array (
			'version' => 3,
	);
	
	/*
	 * if contact_id empty or not numeric, error
	*/
	if (!isset($inparms['contact_id'])) {
		return civicrm_api3_create_error("Geen contact_id in parms in
            dgwcontact_groupget");
	} else {
		$contact_id = trim($inparms['contact_id']);
	}
	
	if (empty($contact_id)) {
		return civicrm_api3_create_error( 'Leeg contact_id voor
            dgwcontact_groupget' );
	} else {
		if (!is_numeric($contact_id)) {
			return civicrm_api3_create_error( 'Contact_id '.$contact_id.' heeft
                niet numerieke waarden in dgwcontact_groupget');
		}
	}

	$civiparms['contact_id'] = $contact_id;


	/**
	 * Use the group api
	 */
	$civires1 = civicrm_api('GroupContact', 'get', $civiparms);
	if (civicrm_error($civires1)) {
		return civicrm_api3_create_error($civires1['error_message']);
	}

	$i = 1;
	foreach ($civires1['values'] as $result) {		
		$data = $result;
		 
		$data['group_id'] = $data['id'];
		unset($data['id']);
		
		$data['contact_id'] = $contact_id;
		
		$data['group_title'] = $data['title'];
		unset($data['title']);
		 
		$outparms[$i] = $data;
		$i++;
	}
	$outparms[0]['record_count'] = $i - 1;
	return $outparms;
}
