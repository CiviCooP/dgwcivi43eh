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
function civicrm_api3_dgw_address_get($inparms) {

	/*
	 * initialize output parameter array
	*/
	$outparms = array("");
	$civiparms = array (
			'version' => 3,
	);
	
	/*
	 * if contact_id empty and address_id empty, error
	*/
	if (!isset($inparms['contact_id']) && !isset($inparms['address_id'])) {
		return civicrm_create_error("Geen contact_id of address_id doorgegeven
            in dgwcontact_addressget.");
	}
	
	if (empty($inparms['contact_id']) && empty($inparms['address_id'])) {
		return civicrm_create_error("Contact_id en address_id allebei leeg in
            dgwcontact_addressget.");
	}
	
	/*
	 * if contact_id not numeric, error
	*/
	if (!empty($inparms['contact_id'])) {
		$contact_id = trim($inparms['contact_id']);
		if (!is_numeric($contact_id)) {
			return civicrm_create_error( 'Contact_id '.$contact_id.' heeft
                niet numerieke waarden in dgwcontact_addressget');
		}
	}
	$civiparms['contact_id'] = $contact_id;
	
	if (isset($inparms['address_id']) && !empty($inparms['address_id'])) {
		$civiparms['id'] = $inparms['address_id'];
	}


	/**
	 * Use the adress api
	 */
	$civires1 = civicrm_api('address', 'get', $civiparms);
	if (civicrm_error($civires1)) {
		return civicrm_api3_create_error($civires1['error_message']);
	}

	$i = 1;
	foreach ($civires1['values'] as $result) {
		/* Get location type name */
		$locationType = CRM_Utils_DgwApiUtils::getLocationByid($result['location_type_id']);
		/* Get phone type name */
		$civiparms3 = array('version' => 3, 'id' => $result['location_type_id']);
		$civires3 = civicrm_api('Country', 'getsingle', $civiparms3);
		$country = "";
		if (!civicrm_error($civires3)) {
			$phoneType = $civires3['label'];
		}
		
		$data = $result;
		 
		$data['contact_id'] = $data['id'];
		unset($data['id']);
		
		$data['street_suffix'] = '';
		if (isset($data['street_number_suffix'])) {
			$data['street_suffix'] = $data['street_number_suffix'];
		}
		unset($data['street_number_suffix']);
		if (isset($data['street_unit'])) {
			$data['street_suffix'] .= $data['street_unit'];
			unset($data['street_unit']);
		}
		if (isset($data['country_id'])) {
			if (!empty($data['country_id'])) {
				$data['country'] = CRM_Core_PseudoConstant::country($data['country_id']);
			} else {
				$data['country'] = "";
			}
		}
		
		$data['location_type'] = $locationType;
		
		$data['start_date'] = date("Y-m-d");
		$data['end_date'] = "";
		 
		$outparms[$i] = $data;
		$i++;
	}
	$outparms[0]['record_count'] = $i - 1;
	return $outparms;
}
