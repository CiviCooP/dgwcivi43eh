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
function civicrm_api3_dgw_email_get($inparms) {

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
	if (!isset($inparms['contact_id']) && !isset($inparms['email_id'])) {
		return civicrm_api3_create_error("Geen contact_id of email_id doorgegeven in
            dgwcontact_emailget.");
	}

	if (empty($inparms['contact_id']) && empty($inparms['email_id'])) {
		return civicrm_api3_create_error("Contact_id en email_id allebei leeg in
            dgwcontact_emailget.");
	}

	/*
	 * if contact_id is used and contains non-numeric data, error
	*/
	if (!empty($inparms['contact_id'])) {
		if (!is_numeric($inparms['contact_id'])) {
			return civicrm_api3_create_error("Contact_id bevat ongeldige waarde in
                dgwcontact_emailget.");
		} else {
			$civiparms['contact_id'] = $inparms['contact_id'];
		}
	}

	/*
	 * if phone_id is used and contains non-numeric data, error
	*/
	if (!empty($inparms['email_id']) && !is_numeric($inparms['email_id'])) {
		return civicrm_api3_create_error("email_id bevat ongeldige waarde in
            dgwcontact_emailget.");
	} else if (!empty($inparms['email_id'])) {
		$civiparms['email_id'] = $inparms['email_id'];
		unset($civiparms['contact_id']); //phone id is use to request a specific phonenumber
	}

	/**
	 * Use the email api
	 */
	$civires1 = civicrm_api('email', 'get', $civiparms);
	if (civicrm_error($civires1)) {
		return civicrm_api3_create_error($civires1['error_message']);
	}

	$i = 1;
	foreach ($civires1['values'] as $result) {
		/* Get location type name */
		$locationType = CRM_Utils_DgwApiUtils::getLocationByid($result['location_type_id']);
		
		$data = $result;
		 
		$data['email_id'] = $data['id'];
		unset($data['id']);
		
		$data['location_type'] = $locationType;
		
		$data['start_date'] = date("Y-m-d");
		$data['end_date'] = "";
		 
		$outparms[$i] = $data;
		$i++;
	}
	$outparms[0]['record_count'] = $i - 1;
	return $outparms;
}
