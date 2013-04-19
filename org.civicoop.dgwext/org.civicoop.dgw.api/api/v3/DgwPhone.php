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
function civicrm_api3_dgw_phone_get($inparms) {

	/*
	 * initialize output parameter array
	*/
	$outparms = array("");
	$civiparms = array (
			'version' => 3,
	);

	/*
	 * if contact_id empty and phone_id empty, error
	*
	* @Todo write a spec function
	*/
	if (!isset($inparms['contact_id']) && !isset($inparms['phone_id'])) {
		return civicrm_api3_create_error("Geen contact_id of phone_id doorgegeven in
            dgwcontact_phoneget.");
	}

	if (empty($inparms['contact_id']) && empty($inparms['phone_id'])) {
		return civicrm_api3_create_error("Contact_id en phone_id allebei leeg in
            dgwcontact_phoneget.");
	}

	/*
	 * if contact_id is used and contains non-numeric data, error
	*/
	if (!empty($inparms['contact_id'])) {
		if (!is_numeric($inparms['contact_id'])) {
			return civicrm_api3_create_error("Contact_id bevat ongeldige waarde in
                dgwcontact_phoneget.");
		} else {
			$civiparms['contact_id'] = $inparms['contact_id'];
		}
	}

	/*
	 * if phone_id is used and contains non-numeric data, error
	*/
	if (!empty($inparms['phone_id']) && !is_numeric($inparms['phone_id'])) {
		return civicrm_api3_create_error("Phone_id bevat ongeldige waarde in
            dgwcontact_phoneget.");
	} else if (!empty($inparms['phone_id'])) {
		$civiparms['phone_id'] = $inparms['phone_id'];
		unset($civiparms['contact_id']); //phone id is use to request a specific phonenumber
	}

	/**
	 * Use the phone api
	 */
	$civires1 = civicrm_api('phone', 'get', $civiparms);
	if (civicrm_error($civires1)) {
		return civicrm_api3_create_error($civires1['error_message']);
	}

	$i = 1;
	foreach ($civires1['values'] as $result) {
		/* Get location type name */
		$civiparms2 = array('version' => 3, 'id' => $result['location_type_id']);
		$civires2 = civicrm_api('LocationType', 'getsingle', $civiparms2);
		$locationType = "";
		if (!civicrm_error($civires2)) {
			$locationType = $civires2['name'];
		}
		/* Get phone type name */
		$civiparms3 = array('version' => 3, 'id' => $result['location_type_id']);
		$civires3 = civicrm_api('OptionValue', 'getsingle', $civiparms3);
		$phoneType = "";
		if (!civicrm_error($civires3)) {
			$phoneType = $civires3['label'];
		}
		
		$data = $result;
		 
		$data['phone_id'] = $data['id'];
		unset($data['id']);
		
		$data['location_type'] = $locationType;
		$data['phone_type'] = $phoneType;
		
		$data['start_date'] = date("Y-m-d");
		$data['end_date'] = "";
		 
		$outparms[$i] = $data;
		$i++;
	}
	$outparms[0]['record_count'] = $i - 1;
	return $outparms;
}
