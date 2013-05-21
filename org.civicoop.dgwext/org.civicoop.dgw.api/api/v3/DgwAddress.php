<?php

/*
+--------------------------------------------------------------------+
| CiviCRM version 4.3                                                |
+--------------------------------------------------------------------+
| Copyright CiviCoop Academic Free License v3.02013                  |
+--------------------------------------------------------------------+
*/

/*
 * Function to update an individual address in CiviCRM
* incoming is either address or adr_refno
*/
function civicrm_api3_dgw_address_update($inparms) {
	/*
	 * if no address_id or adr_refno passed, error
	*/
	if (!isset($inparms['address_id']) && !isset($inparms['adr_refno'])) {
		return civicrm_api3_create_error("Address_id en adr_refno ontbreken beiden");
	}
	if (isset($inparms['address_id'])) {
		$address_id = trim($inparms['address_id']);
	} else {
		$address_id = null;
	}
	if (isset($inparms['adr_refno'])) {
		$adr_refno = trim($inparms['adr_refno']);
	} else {
		$adr_refno = null;
	}
	if (empty($address_id) && empty($adr_refno)) {
		return civicrm_api3_create_error("Address_id en adr_refno ontbreken beiden");
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
	/*
	 * if $adr_refno is used, retrieve address from synchronisation First table
	*/
	/*
	 * if $cde_refno is used, retrieve $address_id from synchronisation First table
	*/
	if (!empty($cde_refno)) {
		$address_id = CRM_Utils_DgwApiUtils::getEntityIdFromSyncTable($cde_refno, 'address');
	}
	/*
	 * if $address_id is still empty, error
	*/
	if (empty($address_id)) {
		return civicrm_api3_create_error("Adres niet gevonden");
	}
	/*
	 * check if address exists in CiviCRM
	*/
	$checkparms = array("address_id" => $address_id, 'version' => 3);
	$res_check = civicrm_api('Address', 'getsingle', $checkparms);
	if (civicrm_error($res_check)) {
		return civicrm_api3_create_error("Adres niet gevonden");
	}
	$contactID = $res_check['contact_id'];
	/*
	 * if location_type is invalid, error
	*/
	$params = array();
	$params['version'] = 3;
	$params['address_id'] = $address_id;
	if (isset($inparms['location_type'])) {
		$location_type_id = CRM_Utils_DgwApiUtils::getLocationIdByName($location_type);
		if ($location_type_id == "") {
			return civicrm_api3_create_error("Location_type is ongeldig");
		}
		$params['location_type_id'] = $location_type_id;
	} else {
		$location_type = "";
	}
	/*
	 * if is_primary is not 0 or 1, error
	*/
	if (isset($inparms['is_primary'])) {
		$is_primary = $inparms['is_primary'];
		if ($is_primary != 0 && $is_primary != 1) {
			return civicrm_api3_create_error("Is_primary is ongeldig");
		}
		$params['is_primary'] = $is_primary;
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
		if ($location_type == "oud" && (empty($end_date) || $end_date > date("Ymd"))) {
			return civicrm_api3_create_error("Combinatie location_type en start/end_date ongeldig");
		}
	}
	/*
	 * if postal_code passed an format invalid, error
	*/
	if (isset($inparms['postal_code']) && !empty($inparms['postal_code'])) {
		$valid_postal = CRM_Utils_DgwUtils::checkPostcodeFormat(trim($inparms['postal_code']));
		if (!$valid_postal) {
			return civicrm_api3_create_error("Postcode is ongeldig");
		} else {
			$postal_code = trim($inparms['postal_code']);
		}
		$params['postal_code'] = $postal_code;
	}
	/*
	 * if country_iso passed, check if country iso code exists in CiviCRM
	*/
	if (isset($inparms['country_iso']) && !empty($inparms['country_iso'])) {
		$country_iso = trim($inparms['country_iso']);
		$countries = CRM_Core_PseudoConstant::countryIsoCode();
		$country_id = array_search($country_iso, $countries);
		if (!$country_id) {
			return civicrm_api3_create_error("Country_iso $country_iso komt niet voor");
		}
		$params['country_id'] = $country_id;
	}

	/*
	 * if street_number passed and not numeric, error
	*/
	if (isset($inparms['street_number'])) {
		$street_number = trim($inparms['street_number']);
		if (!empty($street_number) && !is_numeric($street_number)) {
			return civicrm_api3_create_error( "Huisnummer is niet numeriek");
		} elseif(!empty($street_number)) {
			$params['street_number'] = $street_number;
		}
	}

	$thuisID = CRM_Utils_DgwApiUtils::getLocationIdByName("Thuis");
	$oudID =  CRM_Utils_DgwApiUtils::getLocationIdByName("Oud");
	if ($thuisID == "" || $oudID == "") {
		return civicrm_api3_create_error("Location types zijn niet geconfigureerd");
	}

	/*
	 * all validation passed
	*/
	if (isset($inparms['street_name'])) {
		$params['street_name'] = trim($inparms['street_name']);
	}
	if (isset($inparms['street_suffix'])) {
		$params['street_unit'] = trim($inparms['street_suffix']);
	}
	if (isset($inparms['city'])) {
		$params['city'] = trim($inparms['city']);
	}
	/*
	* compute street address
	*/
	$street_address = $res_check['street_name'];
	if (isset($params['street_name'])) {
		$street_address = $params['street_name'];
	}
	if (isset($params['street_number'])) {
		$street_address = $street_address." ".$params['street_number'];
	} elseif (isset($res_check['street_number'])) {
		$street_address = $street_address." ".$res_check['street_number'];
	}
	if (!empty($params['street_suffix'])) {
		$street_address = $street_address.$params['street_suffix'];
	} elseif (isset($res_check['street_unit'])) {
		$street_address = $street_address.$res_check['street_unit'];
	}
	$params['street_address'] = trim($street_address);
	/*
	* if location_type = toekomst or oud, set start and end date in add.
	* field
	*/
	if ($location_type == "oud" || $location_type == "toekomst") {
		if (isset($start_date) && !empty($start_date)) {
			$datum = date("d-m-Y", strtotime($start_date));
			$sup = "(Vanaf $datum";
		}
		if (isset($end_date) && !empty($end_date)) {
			$datum = date("d-m-Y", strtotime($end_date));
			if (isset($sup) && !empty($sup)) {
				$sup = $sup." tot $datum)";
			} else {
				$sup = "(Tot $datum)";
			}
		} else {
			$sup = $sup.")";
		}
	}
	/*
	 * issue 132: set supplemental address for end date if not location type oud
	*/
	if ($location_type != "oud" && isset($end_date) && !empty($end_date)) {
		$datum = date("d-m-Y", strtotime($end_date));
		$sup = "(Tot $datum)";
	}
	/*
	* issue 139, Erik Hommel, 30 nov 2010
	* If current location_type_id = 1 and end_date is passed
	* as parm and not in future, make address "Oud"
	*/
	if (isset($end_date) && !empty($end_date)) {
		if ($res_check['location_type_id'] == $thuisID && $end_date <= date("Ymd")) {
			$params['location_type_id'] = $oudID;
			_removeAllOudAddresses($contactID, $oudID);
		}
	}
	/*
	* update address with new values
	*/
	if (isset($sup) && !empty($sup)) {
		$params['supplemental_address_1'] = $sup;
	}
	if (isset($start_date) && !empty($start_date)) {
		$params['start_date'] = $start_date;
	}
	if (isset($end_date) && !empty($end_date)) {
		$params['end_date'] = $end_date;
	}

        if ( isset( $inparms['hook_context'] ) ) {
            $params['hook_context'] = $inparms['hook_context'];
        } else {
            $params['hook_context'] = "dgwapi.no_sync";
        }
	$res_update = civicrm_api('Address', 'Create', $params);
	if (civicrm_error($res_update)) {
		return civicrm_api3_create_error('Onbekende fout: '.$res_update['error_msg']);
	}
	/*
	 * issue 158: if the address belongs to a hoofdhuurder, update the household
	* address too
	*/
	$huishoudenID = CRM_Utils_DgwApiUtils::is_hoofdhuurder($contactID);
	if ($huishoudenID != 0) {
		/*
		* update huishouden address if there is one, if not create
		*/
		$hh_parms['version'] = '3';
		$hh_parms['contact_id'] = $huishoudenID;
		$hh_check = civicrm_api('Address', 'get', $hh_parms);
		if (!civicrm_error($hh_check)) {
			//update existing addresses
			if (is_array($hh_check['values']) && count($hh_check['values'])) {
				//update first address of house hold
				$hh_check = reset($hh_check['values']);
				$params['address_id'] = $hh_check['address_id'];
			} else {
				//insert address
				unset($params['address_id']);
			}
		}
		civicrm_api('Address', 'create', $params);
	}
	/*
	* set new adr_refno in synctable if passed
	*/
	if (isset($inparms['adr_refno']) && !empty($inparms['adr_refno'])) {
		$refno = trim($inparms['cde_refno']);
		$key_first_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('key_first');
		$group = CRM_Utils_DgwApiUtils::retrieveCustomGroupByName('Synchronisatie_First_Noa');
		$fields = CRM_Utils_DgwApiUtils::retrieveCustomValuesForContactAndCustomGroupSorted($res_check['contact_id'], $group['id']);
		$fid = "";
		foreach($fields as $key => $field) {
			if ($field['entity_id'] == $address_id  && $field['entity'] == "address") {
				$fid = ":".$key;
				break;
			}
		}

		$civiparms2 = array (
				'version' => 3,
				'entity_id' => $res_check['contact_id'],
				'custom_'.$key_first_field['id'].$fid => $inparms['cde_refno'],
		);

		$civicres2 = civicrm_api('CustomValue', 'Create', $civiparms2);
	}
	/*
	* issue 239: if there is only one address left, make this primary
	*/
	$outparms['is_error'] = "0";
	return $outparms;
}


/*
 * Function to delete an address in CiviCRM
*/
function civicrm_api3_dgw_address_delete($inparms) {
	/*
	 * if no address_id or adr_refno passed, error
	*/
	if (!isset($inparms['address_id']) && !isset($inparms['adr_refno'])) {
		return civicrm_api3_create_error("Address_id en adr_refno ontbreken beiden");
	}
	if (isset($inparms['address_id'])) {
		$address_id = trim($inparms['address_id']);
	} else {
		$address_id = null;
	}
	if (isset($inparms['adr_refno'])) {
		$adr_refno = trim($inparms['adr_refno']);
	} else {
		$adr_refno = null;
	}
	if (empty($address_id) && empty($adr_refno)) {
		return civicrm_api3_create_error("Address_id en adr_refno ontbreken beiden");
	}
	/*
	 * if $adr_refno is used, retrieve $address_id from synchronisation First table
	*/
	if (!empty($adr_refno)) {
		$address_id = CRM_Utils_DgwApiUtils::getEntityIdFromSyncTable($adr_refno, 'address');
	}
	/*
	 * if $address_id is still empty, error
	*/
	if (empty($address_id)) {
		return civicrm_api3_create_error("Adres niet gevonden");
	}
	/*
	 * check if address exists in CiviCRM
	*/
	$checkparms = array("address_id" => $address_id, 'version' => 3);
	$res_check = civicrm_api('Address', 'getsingle', $checkparms);
	if (civicrm_error($res_check)) {
		return civicrm_api3_create_error("Adres niet gevonden");
	}
	/*
	 * all validation passed, delete address from table
	*/
        $address = array(
            'version'   =>  3,
            'id'        =>  $address_id
            );
        if ( isset( $inparms['hook_context'] ) ) {
            $address['hook_context'] = $inparms['hook_context'];
        } else {
            $address['hook_context'] = "dgwapi.no_sync";
        }
	$res = civicrm_api( 'Address', 'delete', $address );
	$outparms['is_error'] = "0";
	return $outparms;
}

function civicrm_api3_dgw_address_create($inparms) {
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
	 * if no street_name passed, error
	*/
	if (!isset($inparms['street_name'])) {
		return civicrm_api3_create_error("Street_name ontbreekt");
	} else {
		$street_name = trim($inparms['street_name']);
	}
	/*
	 * if no city passed, error
	*/
	if (!isset($inparms['city'])) {
		return civicrm_api3_create_error("City ontbreekt");
	} else {
		$city = trim($inparms['city']);
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
	 * if country_iso does not exist in CiviCRM, error
	*/
	if (isset($inparms['country_iso'])) {
		$country_iso = trim($inparms['country_iso']);
		$countries = CRM_Core_PseudoConstant::countryIsoCode();
		$country_id = array_search($country_iso, $countries);
		if (!$country_id) {
			return civicrm_api3_create_error("Country_iso ".$country_iso." komt niet voor");
		}
	}
	/*
	 * if postcode entered and invalid format, error
	*/
	if (isset($inparms['postal_code'])) {
		$postcode = trim($inparms['postal_code']);
		$valid = CRM_Utils_DgwUtils::checkPostcodeFormat($postcode);
		if (!$valid) {
			return civicrm_api3_create_error("Postcode ".$postcode." is ongeldig");
		}
	}
	/*
	 * all validation passed
	 */
	$thuisID = CRM_Utils_DgwApiUtils::getLocationIdByName("Thuis");
	$oudID =  CRM_Utils_DgwApiUtils::getLocationIdByName("Oud");
	if ($thuisID == "" || $oudID == "") {
		return civicrm_api3_create_error("Location types zijn niet geconfigureerd");
	}
	/*
	 * issue 132 : if new address has type Thuis, check if there is
	* already an address Thuis. If so, move the current Thuis to
	* location type Oud first
	*/
	if ($location_type_id == $thuisID) {
		_replaceCurrentAddress($contact_id, $thuisID, $oudID);

		/*
		 * issue 158: if location_type = Thuis, is_primary = 1
		*/
		if ($location_type_id == $thuisID) {
			$is_primary = 1;
		}
	}


	/*
	 *  Add address to contact with standard civicrm function civicrm_location_add
	*/
	$address = array(
			"location_type_id" =>  $location_type_id,
			"is_primary"       =>  $is_primary,
			"city"             =>  $city,
			"street_address"   =>  "",
			'contact_id'       => $contact_id,
			'version'          => 3,
	);

	if (isset($street_name)) {
		$address['street_name'] = $street_name;
		$address['street_address'] = $street_name;
	}
	if (isset($inparms['street_number'])) {
		$address['street_number'] = trim($inparms['street_number']);
		if (empty($address[street_address])) {
			$address['street_address'] = trim($inparms['street_number']);
		} else {
			$address['street_address'] = $address['street_address']." ".trim($inparms['street_number']);
		}
	}
	if (isset($inparms['street_suffix'])) {
		$address['street_unit'] = trim($inparms['street_suffix']);
		if (empty($address['street_address'])) {
			$address['street_address'] = trim($inparms['street_suffix']);
		} else {
			$address['street_address'] = $address['street_address']." ".trim($inparms['street_suffix']);
		}
	}
	if (isset($postcode)) {
		$address['postal_code'] = $postcode;
	}
	if (isset($country_id)) {
		$address['country_id'] = $country_id;
	}
	/*
	 * if location_type = toekomst or oud, set start and end date in add field
	 * */
	 if ($location_type == "oud" || $location_type == "toekomst") {
	 	if (isset($start_date) && !empty($start_date)) {
	 		$datum = date("d-m-Y", strtotime($start_date));
	 		$address['supplemental_address_1'] = "(Vanaf $datum";
	 	}
	 	if (isset($end_date) && !empty($end_date)) {
	 		$datum = date("d-m-Y", strtotime($end_date));
	 		if (isset($address['supplemental_address_1']) && !empty($address['supplemental_address_1'])) {
	 			$address['supplemental_address_1'] = $address['supplemental_address_1']." tot ".$datum.")";
	 		} else {
	 			$address['supplemental_address_1'] = "(Tot ".$datum.")";
	 		}
	 	}
	 }
         if ( isset( $inparms['hook_context'] ) ) {
             $address['hook_context'] = $inparms['hook_context'];
         } else {
             $address['hook_context'] = "dgwapi.no_sync";
         }

	 $res_adr = civicrm_api('Address', 'create', $address);
	 if (civicrm_error($res_adr)) {
	 	return civicrm_api3_create_error("Onverwachte fout van CiviCRM, adres kon niet gemaakt worden, melding : ".$res_adr['error_message']);
	 } else {
	 	/*
	 	 * retrieve address_id from result array
	 	*/
	 	$address_id = $res_adr['id'];

	 	/*
	 	 * for synchronization with First Noa, add record to table for
	 	* synchronization if adr_refno passed as parameter
	 	*/
	 	if (isset($inparms['adr_refno'])) {
	 		$action_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('action');
	 		$entity_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('entity');
	 		$entity_id_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('entity_id');
	 		$key_first_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('key_first');

	 		$civiparms5 = array (
	 				'version' => 3,
	 				'entity_id' => $contact_id,
	 				'custom_'.$action_field['id'] => "none",
	 				'custom_'.$entity_field['id'] => "address",
	 				'custom_'.$entity_id_field['id'] => $address_id,
	 				'custom_'.$key_first_field['id'] => $inparms['adr_refno'],
	 		);
	 		$civicres5 = civicrm_api('CustomValue', 'Create', $civiparms2);
	 	}
	 	/*
	 	 * issue 158: if the address belongs to a hoofdhuurder, add address to
	 	* the household too
	 	*/
	 	$huishouden_id = CRM_Utils_DgwApiUtils::is_hoofdhuurder($contact_id);
	 	if ($huishouden_id != 0) {
	 		/*
	 		 * issue 132 : if new address has type Thuis, check if there is
			 * already an address Thuis. If so, move the current Thuis to
			 * location type Oud first
			 */
	 		if ($location_type_id == $thuisID) {
	 			_replaceCurrentAddress($huishouden_id, $thuisID, $oudID);
	 		}
	 		$address['contact_id'] = $huishouden_id;
	 		$res_adr = civicrm_api('Address', 'create', $address);
	 	}
	 	/*
	 	 * return array
	 	 * */
	 	$outparms['address_id'] = $address_id;
	 	$outparms['is_error'] = "0";
	 }
	 return $outparms;
}

/*
 * Function to get addresss for a contact
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
		return civicrm_api3_create_error("Geen contact_id of address_id doorgegeven
            in dgwcontact_addressget.");
	}

	if (empty($inparms['contact_id']) && empty($inparms['address_id'])) {
		return civicrm_api3_create_error("Contact_id en address_id allebei leeg in
            dgwcontact_addressget.");
	}

	/*
	 * if contact_id not numeric, error
	*/
	if (!empty($inparms['contact_id'])) {
		$contact_id = trim($inparms['contact_id']);
		if (!is_numeric($contact_id)) {
			return civicrm_api3_create_error( 'Contact_id '.$contact_id.' heeft
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
		/* Get address type name */
		$civiparms3 = array('version' => 3, 'id' => $result['location_type_id']);
		$civires3 = civicrm_api('Country', 'getsingle', $civiparms3);
		$country = "";
		if (!civicrm_error($civires3)) {
			$addressType = $civires3['label'];
		}

		$data = $result;

		$data['contact_id'] = $data['id'];
		unset($data['id']);

		$data['street_suffix'] = '';
		if (isset($data['street_unit'])) {
			$data['street_suffix'] = $data['street_unit'];
		}
		unset($data['street_unit']);
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

function _replaceCurrentAddress($contact_id, $thuisID, $oudID) {
	$civiparms2 = array (
			'version' => 3,
			'contact_id' => $contact_id,
			'location_type_id' => $thuisID
	);
	$civires2 = civicrm_api('Address', 'get', $civiparms2);
	if (isset($civires2['values']) && is_array($civires2['values'])) {

		/*
		 * remove all existing addresses with type Oud
		*/
		_removeAllOudAddresses($contact_id, $oudID);

		/*
		 * update current thuis address to location type oud
		*/
		foreach($civires2['values'] as $aid => $address) {
			$civiparms4 = array (
					'version' => 3,
					'id' => $aid,
					'contact_id' => $contact_id,
					'location_type_id' => $oudID,
			);
			$civires4 = civicrm_api('Address', 'create', $civiparms4);
		}
	}
}

function _removeAllOudAddresses($contact_id, $oudID) {
	$civiparms3 = array (
			'version' => 3,
			'contact_id' => $contact_id,
			'location_type_id' => $oudID
	);
	$civires3 = civicrm_api('Address', 'get', $civiparms3);
	if (isset($civires3['values']) && is_array($civires3['values'])) {
		foreach($civires3['values'] as $aid => $address) {
			$civiparms4 = array (
					'version' => 3,
					'id' => $aid,
			);
			$civires4 = civicrm_api('Address', 'delete', $civiparms4);
		}
	}
}