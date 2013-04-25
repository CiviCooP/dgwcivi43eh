<?php

/*
+--------------------------------------------------------------------+
| CiviCRM version 4.3                                                |
+--------------------------------------------------------------------+
| Copyright CiviCoop Academic Free License v3.02013                  |
+--------------------------------------------------------------------+
*/

function civicrm_api3_dgw_phone_create($inparms) {
	/*
	 * if no contact_id or persoonsnummer_first passed, error
	*/
	if (!isset($inparms['contact_id']) && !isset($inparms['persoonsnummer_first'])) {
		return civicrm_api3_create_error("Contact_id en persoonsnummer_first ontbreken beiden");
	}
	if (isset($inparms['contact_id'])) {
		$contact_id = trim($inparms['contact_id']);
	} else {
		$contact_id = null;
	}
	if (isset($inparms['persoonsnummer_first'])) {
		$pers_nr = trim($inparms['persoonsnummer_first']);
	} else {
		$pers_nr = null;
	}
	if (empty($contact_id) && empty($pers_nr)) {
		return civicrm_api3_create_error("Contact_id en persoonsnummer_first ontbreken beiden");
	}
	/*
	 * if no location_type passed, error
	*/
	if (!isset($inparms['location_type'])) {
		return civicrm_api3_create_error("Location_type ontbreekt");
	} else {
		$location_type = strtolower(trim($inparms['location_type']));
	}
	/*
	 * if no is_primary passed, error
	*/
	if (!isset($inparms['is_primary'])) {
		return civicrm_api3_create_error("Is_primary ontbreekt");
	} else {
		$is_primary = trim($inparms['is_primary']);
	}
	/*
	 * if no phone_type passed, error
	*/
	if (!isset($inparms['phone_type'])) {
		return civicrm_api3_create_error("Phone_type ontbreekt");
	} else {
		$phone_type = strtolower(trim($inparms['phone_type']));
	}
	/*
	 * if no phone passed, error
	*/
	if (!isset($inparms['phone'])) {
		return civicrm_api3_create_error("Phone ontbreekt");
	} else {
		$phone = trim($inparms['phone']);
	}
	/*
	 * if start_date passed and format invalid, error
	*/
	if (isset($inparms['start_date']) && !empty($inparms['start_date'])) {
		$valid_date = CRM_Utils_DgwUtils::checkDateFormat($inparms['start_date']);
		if (!$valid_date) {
			return civicrm_api3_create_error("Onjuiste formaat start_date");
		} else {
			$start_date = $inparms['start_date'];
		}
	}
	/*
	 * if end_date passed and format invalid, error
	*/
	if (isset($inparms['end_date']) && !empty($inparms['end_date'])) {
		$valid_date = CRM_Utils_DgwUtils::checkDateFormat($inparms['end_date']);
		if (!$valid_date) {
			return civicrm_api3_create_error("Onjuiste formaat end_date");
		} else {
			$end_date = $inparms['end_date'];
		}
	}
	
	$persoonsnummer_first_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('persoonsnummer_first');
	
	/*
	 * if contact not in civicrm, error
	*/
	if (isset($pers_nr)) {
		$checkparms = array("custom_".$persoonsnummer_first_field['id'] => $pers_nr);
	} else {
		$checkparms = array("contact_id" => $contact_id);
	}
	$checkparms['version'] = 3;
	$check_contact = civicrm_api('Contact', 'get', $checkparms);
	if (civicrm_error($check_contact)) {
		return civicrm_api3_create_error("Contact niet gevonden");
	} else {
		$check_contact = reset($check_contact['values']);
		$contact_id = $check_contact['contact_id'];
	}
	/*
	 * if location_type is invalid, error
	*/
	$location_type_id = CRM_Utils_DgwApiUtils::getLocationIdByName($location_type);
	if ($location_type_id == "") {
		return civicrm_api3_create_error("Location_type is ongeldig");
	}
	/*
	 * if phone_type is invalid, error
	*/	
	$phone_type_id = false;
	$phone_types = CRM_Core_PseudoConstant::phoneType();
	foreach($phone_types as $key => $type) {
		if (strtolower($type) == strtolower($phone_type)) {
			$phone_type_id = $key;
		}
	}
	if ($phone_type_id===false) {
		return civicrm_api3_create_error("Invalid phone type");
	}
	
	/*
	 * if is_primary is not 0 or 1, error
	*/
	if ($is_primary != 0 && $is_primary != 1) {
		return civicrm_api3_create_error("Is_primary is ongeldig");
	}
	/*
	 * if start_date > today and location_type is not toekomst, error
	*/
	if (isset($start_date) && !empty($start_date)) {
		$start_date = date("Ymd", strtotime($start_date));
		if ($start_date > date("Ymd") && $location_type != "toekomst") {
			return civicrm_api3_create_error("Combinatie location_type en start/end_date ongeldig");
		}
		/*
		 * if location_type = toekomst and start_date is not > today, error
		*/
		if ($location_type == "toekomst" && $start_date <= date("Ymd")) {
			return civicrm_api3_create_error("Combinatie location_type en start/end_date ongeldig");
		}
	}
	/*
	 * if end_date < today and location_type is not oud, error
	*/
	if (isset($end_date) && !empty($end_date)) {
		$end_date = date("Ymd", strtotime($end_date));
		if ($end_date < date("Ymd") && $location_type != "oud") {
			return civicrm_api3_create_error("Combinatie location_type en start/end_date ongeldig");
		}
		/*
		 * if location_type = oud and end_date is empty or > today, error
		*/
		if ($location_type == "oud") {
			if (empty($end_date) || $end_date > date("Ymd")) {
				return civicrm_api3_create_error("Combinatie location_type en start/end_date ongeldig");
			}
		}
	}
	
	/*
	 * if location type toekomst or oud, add start and end date after phone
	*/
	if ($location_type == "toekomst") {
		if (isset($start_date) && !empty($start_date)) {
			$datum = date("d-m-Y", strtotime($start_date));
			$phone = $phone." (vanaf $datum)";
		}
	}
	if ($location_type == "oud") {
		if (isset($end_date) && !empty($end_date)) {
			$datum = date("d-m-Y", strtotime($end_date));
			$phone = $phone." (tot $datum)";
		}
	}
	/*
	 * Add phone to contact with standard civicrm function civicrm_location_add
	*/
	$civiparms = array(
			"contact_id"        =>  $contact_id,
			"location_type_id"  =>  $location_type_id,
			"is_primary"        =>  $is_primary,
			"phone_type_id"     =>  $phone_type_id,
			"phone"             =>  $phone,
			"version"			=>  3);
	$res_phone = civicrm_api('Phone', 'Create', $civiparms);
	
	if (civicrm_error($res_phone)) {
		return civicrm_api3_create_error("Onverwachte fout van CiviCRM, phone kon niet gemaakt worden, melding : ".$res_phone['error_message']);
	} else {
		/*
		 * retrieve phone_id from result array
		*/
		$phone_id = $res_phone['id'];
		/*
		 * for synchronization with First Noa, add record to table for
		* synchronization if cde_refno passed as parameter
		*/
		if (isset($inparms['cde_refno'])) {
			$action_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('action');
			$entity_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('entity');
			$entity_id_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('entity_id');
			$key_first_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('key_first');
			
			$civiparms2 = array (
				'version' => 3,
				'entity_id' => $contact_id,
				'custom_'.$action_field['id'] => "none",
				'custom_'.$entity_field['id'] => "phone",
				'custom_'.$entity_id_field['id'] => $phone_id,
				'custom_'.$key_first_field['id'] => $inparms['cde_refno'],
			);
			
			$civicres2 = civicrm_api('CustomValue', 'Create', $civiparms2);
		}
	}
	/*
	 * issue 158: if the phone belongs to a hoofdhuurder, add a phone to the
	* household too
	*/
	$huishouden_id = CRM_Utils_DgwApiUtils::is_hoofdhuurder($contact_id);
	if ($huishouden_id != 0) {
		/*
		 * add phone to huishouden
		*/
		$civiparms = array(
			"contact_id"        =>  $huishouden_id,
			"location_type_id"  =>  $location_type_id,
			"is_primary"        =>  $is_primary,
			"phone_type_id"     =>  $phone_type_id,
			"phone"             =>  $phone,
			"version"			=>  3);
		$res_phone = civicrm_api('Phone', 'Create', $civiparms);
	}
	/*
	 * return array
	*/
	$outparms['phone_id'] = $phone_id;
	$outparms['is_error'] = "0";
	return $outparms;
}

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
		$locationType = CRM_Utils_DgwApiUtils::getLocationByid($result['location_type_id']);
		/* Get phone type name */
		$civiparms3 = array('version' => 3, 'id' => $result['phone_type_id']);
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
