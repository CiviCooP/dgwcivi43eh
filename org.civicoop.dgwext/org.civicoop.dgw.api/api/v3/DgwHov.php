<?php

/*
+--------------------------------------------------------------------+
| CiviCRM version 4.3                                                |
+--------------------------------------------------------------------+
| Copyright CiviCoop Academic Free License v3.02013                  |
+--------------------------------------------------------------------+
*/

function civicrm_api3_dgw_hov_create($inparms) {
	$outparms['is_error'] = '1';
	
	/*
	 * if no hov_nummer passed, error
	 * */
	if (!isset($inparms['hov_nummer'])) {
		return civicrm_api3_create_error("Hov_nummer ontbreekt");
	} else {
		$hov_nummer = trim($inparms['hov_nummer']);
	}
	/*
	 * Corr_name can not be empty
	*/
	if (!isset($inparms['corr_name']) || empty($inparms['corr_name'])) {
		return civicrm_api3_create_error("Corr_name ontbreekt");
	} else {
		$corr_name = trim($inparms['corr_name']);
	}
	
	/*
	 * if no hh_persoon passed and no mh_persoon passed, error
	*/
	if (!isset($inparms['hh_persoon']) && !isset($inparms['mh_persoon'])) {
		return civicrm_api3_create_error("Hoofdhuurder of medehuurder ontbreekt");
	} else {
		if (isset($inparms['hh_persoon'])) {
			$hh_persoon = trim($inparms['hh_persoon']);
		} else {
			$hh_persoon = null;
		}
		if (isset($inparms['mh_persoon'])) {
			$mh_persoon = trim($inparms['mh_persoon']);
		} else {
			$mh_persoon = null;
		}
	}
	/*
	 * if hh_persoon and mh_persoon empty, error
	*/
	if (empty($hh_persoon) && empty($mh_persoon)) {
		return civicrm_api3_create_error("Hoofdhuurder of medehuurder ontbreekt");
	}
	
	$persoonsnummer_first_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('persoonsnummer_first');
	
	/*
	 * if hh_persoon not found in CiviCRM, error
	 */
	$hh_type = null;
	if (!empty($hh_persoon)) {
		$hhparms = array("custom_".$persoonsnummer_first_field['id'] => $hh_persoon);
		$hhparms['version'] = 3;
		$res_hh = civicrm_api('Contact', 'get', $hhparms);
		if (civicrm_error($res_hh)) {
			return civicrm_api3_create_error("Hoofdhuurder niet gevonden");
		} else {
			$res_hh = reset($res_hh['values']);
			$hh_type = strtolower($res_hh['contact_type']);
			$hh_id = $res_hh['contact_id'];
		}
	}
	/*
	 * if mh_persoon not found in CiviCRM, error
	*/
	if (!empty($mh_persoon)) {
		$mhparms = array("custom_".$persoonsnummer_first_field['id'] => $mh_persoon);
		$mhparms['version'] = 3;
		$res_mh = civicrm_api('Contact', 'get', $mhparms);
		if (civicrm_error($res_mh)) {
			return civicrm_api3_create_error("Medehuurder niet gevonden");
		} else {
			$res_mh = reset($res_mh['values']);
			$mh_id = $res_hh['contact_id'];
		}
	}
	/*
	 * if start_date passed and format invalid, error
	*/
	if (isset($inparms['start_date']) && !empty($inparms['start_date'])) {
		$valid_date = CRM_Utils_DgwUtils::checkDateFormat($inparms['start_date']);
		if (!$valid_date) {
			return civicrm_api3_create_error("Onjuiste formaat start_date");
		} else {
			$start_date = date("Ymd", strtotime($inparms['start_date']));
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
			$end_date = date("Ymd", strtotime($inparms['end_date']));
		}
	}
	/*
	 * if hh_start_date passed and format invalid, error
	*/
	if (isset($inparms['hh_start_date']) && !empty($inparms['hh_start_date'])) {
		$valid_date = CRM_Utils_DgwUtils::checkDateFormat($inparms['hh_start_date']);
		if (!$valid_date) {
			return civicrm_api3_create_error("Onjuiste formaat hh_start_date");
		} else {
			$hh_start_date = date("Ymd", strtotime($inparms['hh_start_date']));
		}
	}
	/*
	 * if hh_end_date passed and format invalid, error
	*/
	if (isset($inparms['hh_end_date']) && !empty($inparms['hh_end_date'])) {
		$valid_date = CRM_Utils_DgwUtils::checkDateFormat($inparms['hh_end_date']);
		if (!$valid_date) {
			return civicrm_api3_create_error("Onjuiste formaat hh_end_date");
		} else {
			$hh_end_date = date("Ymd", strtotime($inparms['hh_end_date']));
		}
	}
	/*
	 * if mh_start_date passed and format invalid, error
	*/
	if (isset($inparms['mh_start_date']) && !empty($inparms['mh_start_date'])) {
		$valid_date = CRM_Utils_DgwUtils::checkDateFormat($inparms['mh_start_date']);
		if (!$valid_date) {
			return civicrm_api3_create_error("Onjuiste formaat mh_start_date");
		} else {
			$mh_start_date = date("Ymd", strtotime($inparms['mh_start_date']));
		}
	}
	/*
	 * if mh_end_date passed and format invalid, error
	*/
	if (isset($inparms['mh_end_date']) && !empty($inparms['mh_end_date'])) {
		$valid_date = CRM_Utils_DgwUtils::checkDateFormat($inparms['mh_end_date']);
		if (!$valid_date) {
			return civicrm_api3_create_error("Onjuiste formaat mh_end_date");
		} else {
			$mh_end_date = date("Ymd", strtotime($inparms['mh_end_date']));
		}
	}
	
	$hov_group = CRM_Utils_DgwApiUtils::retrieveCustomGroupByName('Huurovereenkomst');
	if (!is_array($hov_group)) {
		return civicrm_api3_create_error("CustomGroup Huurovereenkomst niet gevonden");
	}
	$hov_group_id = $hov_group['id'];
	
	$hov_group_org = CRM_Utils_DgwApiUtils::retrieveCustomGroupByName('Huurovereenkomst__org_');
	if (!is_array($hov_group_org)) {
		return civicrm_api3_create_error("CustomGroup Huurovereenkomst Org niet gevonden");
	}
	$hov_group_org_id = $hov_group_org['id'];
	
	/*
	 * Validation passed, processing depends on contact type (issue 240)
	* Huurovereenkomst can be for individual or organization
	*/
	if ($hh_type == "organization") {
		$customparms['version'] = 3;
		$customparms['entity_id'] = $hh_id;
		
		/*
		 * check if huurovereenkomst already exists
		*/
		$values = CRM_Utils_DgwApiUtils::retrieveCustomValuesForContactAndCustomGroupSorted($hh_id, $hov_group_org_id);
		$key = ""; //update alle records van een huurovereenkomt, als leeg nieuwe invoegen
		foreach($values as $id => $field) {
			if ($field['hov_nummer'] == $hov_nummer) {
				$key = ':'.$id;
				break; //stop loop
			}
		}
		
		$hov_nummer_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('hov_nummer');
		$begindatum_overeenkomst_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('begindatum_overeenkomst');
		$einddatum_overeenkomst_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('einddatum_overeenkomst');
		$vge_nummer_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('vge_nummer');
		$vge_adres_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('vge_adres');
		$naam_op_overeenkomst_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('naam_op_overeenkomst');
		
		$customparms['custom_'.$hov_nummer_field['id'].$key] = $hov_nummer;
		if (isset($start_date) && !empty($start_date)) {
			$customparms['custom_'.$begindatum_overeenkomst_field['id'].$key] = $start_date;
		}
		if (isset($end_date) && !empty($end_date)) {
			$customparms['custom_'.$einddatum_overeenkomst_field['id'].$key] = $end_date;
		}
		if (isset($inparms['vge_nummer'])) {
			$customparms['custom_'.$vge_nummer_field['id'].$key] = trim($inparms['vge_nummer']);
		}
		if (isset($inparms['vge_adres'])) {
			$customparms['custom_'.$vge_adres_field['id'].$key] = trim($inparms['vge_adres']);
		}
		if (isset($inparms['corr_name'])) {
			$customparms['custom_'.$naam_op_overeenkomst_field['id'].$key] = trim($inparms['corr_name']);
		}
		
		
		$res_custom = civicrm_api('CustomValue', 'Create', $customparms);
		if (civicrm_error($res_custom)) {
			return civicrm_api3_create_error($res_custom['error_message']);
		}
		$outparms['is_error'] = 0;
	} else {
		$medehuurders = 0;
		$huishouden_id = 0;
		
		/*
		 * huurovereenkomst for individual (household)
		 */
		$values = CRM_Utils_DgwApiUtils::retrieveCustomValuesForContactAndCustomGroupSorted($hh_id, $hov_group_id);
		$key = ""; //update alle records van een huurovereenkomt, als leeg nieuwe invoegen
		foreach($values as $id => $field) {
			if ($field['HOV_nummer_First'] == $hov_nummer) {
				$key = ':'.$id;
				break; //stop loop
			}
		}
		
		if (!strlen($key)) {
			$huishouden_id = CRM_Utils_DgwApiUtils::is_hoofdhuurder($hh_id);
			$medehuurders = 0;
			if ($huishouden_id) {
				$medehuurders = CRM_Utils_DgwApiUtils::aantalMedehuurders($huishouden_id);
			}
		}
		
		if (!$medehuurders) {
			//huishouden bestaat niet, huishouden aanmaken
			$hh_parms['version'] = '3';
			$hh_parms['contact_type'] = "Household";
			$hh_parms['household_name'] = $inparms['corr_name'];
			$hh_res = civicrm_api('Contact', 'Create', $hh_parms);			
			if (civicrm_error($hh_res)) {
				return civicrm_api3_create_error("Onverwachte fout: huishouden niet aangemaakt in CiviCRM, melding : ".$hh_res['error_message']);
			} else {
				$huishouden_id = (int) $hh_res['id'];
			}
			
			/**
			 * Copy address to house hold
			 */
			$addresses = civicrm_api('Address', 'get', array('version' => 3, 'contact_id' => $hh_id));
			if (!civicrm_error($addresses)) {
				foreach($addresses['values'] as $adr) {
					unset($adr['id']);
					$adr['contact_id'] = $huishouden_id;
					$adr['version'] = 3;
					$res = civicrm_api('Address', 'create', $adr);
				}
			}

			/**
			 * Copy phone to house hold
			 */
			$phones = civicrm_api('Phone', 'get', array('version' => 3, 'contact_id' => $hh_id));
			if (!civicrm_error($phones)) {
				foreach($phones['values'] as $phone) {
					unset($phone['id']);
					$phone['contact_id'] = $huishouden_id;
					$phone['version'] = 3;
					civicrm_api('Phone', 'create', $phone);
				}
			}
			
			/**
			 * Copy email to house hold
			 */
			$emails = civicrm_api('Email', 'get', array('version' => 3, 'contact_id' => $hh_id));
			if (!civicrm_error($emails)) {
				foreach($emails['values'] as $email) {
					unset($email['id']);
					$email['contact_id'] = $huishouden_id;
					$email['version'] = 3;
					civicrm_api('Email', 'create', $phone);
				}
			}
		}
		
		$hov_nummer_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('HOV_nummer_First');
		$begindatum_overeenkomst_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('Begindatum_HOV');
		$einddatum_overeenkomst_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('Einddatum_HOV');
		$vge_nummer_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('VGE_nummer_First');
		$vge_adres_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('VGE_adres_First');
		$naam_op_overeenkomst_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('Correspondentienaam_First');
		
		/*
		 * huurovereenkomst aanmaken
		*/
		$customparms['version'] = 3;
		$customparms['entity_id'] = $huishouden_id;
		$customparms['custom_'.$hov_nummer_field['id'].$key] = $hov_nummer;
		if (isset($start_date) && !empty($start_date)) {
			$customparms['custom_'.$begindatum_overeenkomst_field['id'].$key] = $start_date;
		}
		if (isset($end_date) && !empty($end_date)) {
			$customparms['custom_'.$einddatum_overeenkomst_field['id'].$key] = $end_date;
		}
		if (isset($inparms['vge_nummer'])) {
			$customparms['custom_'.$vge_nummer_field['id'].$key] = trim($inparms['vge_nummer']);
		}
		if (isset($inparms['vge_adres'])) {
			$customparms['custom_'.$vge_adres_field['id'].$key] = trim($inparms['vge_adres']);
		}
		if (isset($inparms['corr_name'])) {
			$customparms['custom_'.$naam_op_overeenkomst_field['id'].$key] = trim($inparms['corr_name']);
		}
		$res_custom = civicrm_api('CustomValue', 'Create', $customparms);
		if (civicrm_error($res_custom)) {
			return civicrm_api3_create_error($res_custom['error_message']);
		}
		$outparms['is_error'] = 0;
		
		//update correspondentie naam bij huishouden
		if (isset($inparms['corr_name'])) {
			$cor_parms['version'] = 3;
			$cor_parms['name'] = trim($inparms['corr_name']);
			$cor_parms['contact_id'] = $huishouden_id;
			$res_cor = civicrm_api('Contact', 'Create', $cor_parms);
		}
		
		/*
		 * for both persons, check if a relation hoofdhuurder to household is
		* present. If so, update with incoming dates. If not so, create.
		*/
		if (isset($hh_persoon)) {
			$rel_med_id = CRM_Utils_DgwApiUtils::retrieveRelationshipTypeIdByNameAB('Medehuurder');
			$rel_hfd_id = CRM_Utils_DgwApiUtils::retrieveRelationshipTypeIdByNameAB('Hoofdhuurder');
			$parms = array(
					'version' => 3,
					'relationship_type_id' => $rel_med_id,
					'contact_id_a' => $huishouden_id,
			);
			$res = civicrm_api('Relationship', 'get', $parms);
			$updated = false;
			if (!civicrm_error($res)) {
				foreach($res['values'] as $rid => $value) {
					$rel_params['version'] = 3;
					$rel_params['id'] = $rid;
					$rel_params['relationship_type_id'] = $rel_hfd_id;
					if (isset($hh_start_date) && !empty($hh_start_date)) {
						$rel_params['start_date'] = $hh_start_date;
					}
					if (isset($hh_end_date) && !empty($hh_end_date)) {
						$rel_params['end_date'] = $hh_end_date;
					}
					civicrm_api('Relationship', 'Create', $rel_params);
					$updated = true;
				}
			}
			if (!$updated) {
				$rel_params['version'] = 3;
				$rel_params['contact_id_a'] = $hh_id;
				$rel_params['contact_id_b'] = $huishouden_id;
				$rel_params['is_active'] = 1;
				$rel_params['relationship_type_id'] = $rel_hfd_id;
				if (isset($hh_start_date) && !empty($hh_start_date)) {
					$rel_params['start_date'] = $hh_start_date;
				}
				if (isset($hh_end_date) && !empty($hh_end_date)) {
					$rel_params['end_date'] = $hh_end_date;
				}
				civicrm_api('Relationship', 'Create', $rel_params);
			}
		}
		
		if (isset($mh_persoon) && !empty($mh_persoon)) {
			$rel_med_id = CRM_Utils_DgwApiUtils::retrieveRelationshipTypeIdByNameAB('Medehuurder');
			$rel_hfd_id = CRM_Utils_DgwApiUtils::retrieveRelationshipTypeIdByNameAB('Hoofdhuurder');
			$parms = array(
					'version' => 3,
					'relationship_type_id' => $rel_med_id,
					'contact_id_a' => $mh_id,
			);
			$res = civicrm_api('Relationship', 'get', $parms);
			$updated = false;
			if (!civicrm_error($res)) {
				foreach($res['values'] as $rid => $value) {
					$rel_params['version'] = 3;
					$rel_params['id'] = $rid;
					if (isset($mh_start_date) && !empty($mh_start_date)) {
						$rel_params['start_date'] = $mh_start_date;
					}
					if (isset($mh_end_date) && !empty($mh_end_date)) {
						$rel_params['end_date'] = $mh_end_date;
					}
					civicrm_api('Relationship', 'Create', $rel_params);
					$updated = true;
				}
			}
			if (!$updated) {
				$rel_params['version'] = 3;
				$rel_params['contact_id_a'] = $mh_id;
				$rel_params['contact_id_b'] = $huishouden_id;
				$rel_params['is_active'] = 1;
				$rel_params['relationship_type_id'] = $rel_med_id;
				if (isset($mh_start_date) && !empty($mh_start_date)) {
					$rel_params['start_date'] = $mh_start_date;
				}
				if (isset($mh_end_date) && !empty($mh_end_date)) {
					$rel_params['end_date'] = $mh_end_date;
				}
				civicrm_api('Relationship', 'Create', $rel_params);
			}
		}
	}
	return $outparms;
}

/*
 * Function to update huurovereenkomst
*/
function civicrm_api3_dgw_hov_update($inparms) {
	/*
	 * if no hov_nummer passed, error
	*/
	if (!isset($inparms['hov_nummer'])) {
		return civicrm_api3_create_error("Hov_nummer ontbreekt");
	} else {
		$hov_nummer = trim($inparms['hov_nummer']);
	}
	/*
	 * if hov not found in CiviCRM, error (issue 240 check for
	 * household table and organization table
	 */
	$type = null;
	$org_id = null;
	$huis_id = CRM_Utils_DgwApiUtils::getHovFromTable($hov_nummer, 'HOV_nummer_First');
	if (!$huis_id) {
		$org_id = CRM_Utils_DgwApiUtils::getHovFromTable($hov_nummer, 'hov_nummer');
		if (!$org_id) {
			return civicrm_api3_create_error("Huurovereenkomst niet gevonden");
		}
	}
	
	$persoonsnummer_first_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('persoonsnummer_first');
	
	/*
	 * if hh_persoon passed and not found in CiviCRM, error
	 * issue 240: or if type = organization
	 */
	if (isset($inparms['hh_persoon']) && !empty($inparms['hh_persoon'])) {
		if ($type == "organisatie") {
			return civicrm_api3_create_error("Hoofdhuurder kan niet opgegeven worden bij een huurovereenkomst van een organisatie");
		}
		$hh_persoon = trim($inparms['hh_persoon']);
		$hhparms = array("custom_".$persoonsnummer_first_field['id'] => $hh_persoon);
		$hhparms['version'] = 3;
		$res_hh = civicrm_api('Contact', 'get', $hhparms);
		if (civicrm_error($res_hh)) {
			return civicrm_api3_create_error("Hoofdhuurder niet gevonden");
		}
		$res_hh = reset($res_hh['values']);
		$hh_id = $res_hh['contact_id'];
	}
	
	/*
	 * if mh_persoon passed and not found in CiviCRM, error (also check
	 		* new type for issue 240)
	*/
	if (isset($inparms['mh_persoon']) && !empty($inparms['mh_persoon'])) {
		if ($type == "organisatie") {
			return civicrm_api3_create_error("Medehuurder kan niet opgegeven worden bij een huurovereenkomst van een organisatie");
		}
		$mh_persoon = trim($inparms['mh_persoon']);
		$mhparms = array("custom_".$persoonsnummer_first_field['id'] => $mh_persoon);
		$mhparms['version'] = 3;
		$res_mh = civicrm_api('Contact', 'get', $mhparms);
		if (civicrm_error($res_mh)) {
			return civicrm_api3_create_error("Medehuurder niet gevonden");
		}
		$res_mh = reset($res_mh['values']);
		$mh_id = $res_hh['contact_id'];
	}
	 
	/*
	 * if start_date passed and format invalid, error
	*/
	if (isset($inparms['start_date']) && !empty($inparms['start_date'])) {
		$valid_date = CRM_Utils_DgwUtils::checkDateFormat($inparms['start_date']);
		if (!$valid_date) {
			return civicrm_api3_create_error("Onjuiste formaat start_date");
		} else {
			$start_date = date("Ymd", strtotime($inparms['start_date']));
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
			$end_date = date("Ymd", strtotime($inparms['end_date']));
		}
	}
	/*
	 * if hh_start_date passed and format invalid, error
	*/
	if (isset($inparms['hh_start_date']) && !empty($inparms['hh_start_date'])) {
		$valid_date = CRM_Utils_DgwUtils::checkDateFormat($inparms['hh_start_date']);
		if (!$valid_date) {
			return civicrm_api3_create_error("Onjuiste formaat hh_start_date");
		} else {
			$hh_start_date = date("Ymd", strtotime($inparms['hh_start_date']));
		}
	}
	/*
	 * if hh_end_date passed and format invalid, error
	*/
	if (isset($inparms['hh_end_date']) && !empty($inparms['hh_end_date'])) {
		$valid_date = CRM_Utils_DgwUtils::checkDateFormat($inparms['hh_end_date']);
		if (!$valid_date) {
			return civicrm_api3_create_error("Onjuiste formaat hh_end_date");
		} else {
			$hh_end_date = date("Ymd", strtotime($inparms['hh_end_date']));
		}
	}
	/*
	 * if mh_start_date passed and format invalid, error
	*/
	if (isset($inparms['mh_start_date']) && !empty($inparms['mh_start_date'])) {
		$valid_date = CRM_Utils_DgwUtils::checkDateFormat($inparms['mh_start_date']);
		if (!$valid_date) {
			return civicrm_api3_create_error("Onjuiste formaat mh_start_date");
		} else {
			$mh_start_date = date("Ymd", strtotime($inparms['mh_start_date']));
		}
	}
	/*
	 * if mh_end_date passed and format invalid, error
	*/
	if (isset($inparms['mh_end_date']) && !empty($inparms['mh_end_date'])) {
		$valid_date = CRM_Utils_DgwUtils::checkDateFormat($inparms['mh_end_date']);
		if (!$valid_date) {
			return civicrm_api3_create_error("Onjuiste formaat mh_end_date");
		} else {
			$mh_end_date = date("Ymd", strtotime($inparms['mh_end_date']));
		}
	}
	
	$hov_group = CRM_Utils_DgwApiUtils::retrieveCustomGroupByName('Huurovereenkomst');
	if (!is_array($hov_group)) {
		return civicrm_api3_create_error("CustomGroup Huurovereenkomst niet gevonden");
	}
	$hov_group_id = $hov_group['id'];
	
	$hov_group_org = CRM_Utils_DgwApiUtils::retrieveCustomGroupByName('Huurovereenkomst__org_');
	if (!is_array($hov_group_org)) {
		return civicrm_api3_create_error("CustomGroup Huurovereenkomst Org niet gevonden");
	}
	$hov_group_org_id = $hov_group_org['id'];
	
	/*
	 * Validation passed, process depending on type (issue 240)
	*/
	if ($type == "organisatie") {
		/*
		 * organization: update fields if passed in parms (issue 240)
		 */
		$customparms['version'] = 3;
		$customparms['entity_id'] = $org_id;
		
		/*
		 * check if huurovereenkomst already exists
		*/
		$values = CRM_Utils_DgwApiUtils::retrieveCustomValuesForContactAndCustomGroupSorted($org_id, $hov_group_org_id);
		$key = ""; //update alle records van een huurovereenkomt, als leeg nieuwe invoegen
		foreach($values as $id => $field) {
			if ($field['hov_nummer'] == $hov_nummer) {
				$key = ':'.$id;
				break; //stop loop
			}
		}
		
		$hov_nummer_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('hov_nummer');
		$begindatum_overeenkomst_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('begindatum_overeenkomst');
		$einddatum_overeenkomst_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('einddatum_overeenkomst');
		$vge_nummer_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('vge_nummer');
		$vge_adres_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('vge_adres');
		$naam_op_overeenkomst_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('naam_op_overeenkomst');
		
		$customparms['custom_'.$hov_nummer_field['id'].$key] = $hov_nummer;
		if (isset($start_date) && !empty($start_date)) {
			$customparms['custom_'.$begindatum_overeenkomst_field['id'].$key] = $start_date;
		}
		if (isset($end_date) && !empty($end_date)) {
			$customparms['custom_'.$einddatum_overeenkomst_field['id'].$key] = $end_date;
		}
		if (isset($inparms['vge_nummer'])) {
			$customparms['custom_'.$vge_nummer_field['id'].$key] = trim($inparms['vge_nummer']);
		}
		if (isset($inparms['vge_adres'])) {
			$customparms['custom_'.$vge_adres_field['id'].$key] = trim($inparms['vge_adres']);
		}
		if (isset($inparms['corr_name'])) {
			$customparms['custom_'.$naam_op_overeenkomst_field['id'].$key] = trim($inparms['corr_name']);
		}
		
		$res_custom = civicrm_api('CustomValue', 'Create', $customparms);
		if (civicrm_error($res_custom)) {
			return civicrm_api3_create_error($res_custom['error_message']);
		}
		$outparms['is_error'] = 0;
	} else {
		/*
		 * individual/household
		*/
		$values = CRM_Utils_DgwApiUtils::retrieveCustomValuesForContactAndCustomGroupSorted($huis_id, $hov_group_id);
		$key = ""; //update alle records van een huurovereenkomt, als leeg nieuwe invoegen
		foreach($values as $id => $field) {
			if ($field['HOV_nummer_First'] == $hov_nummer) {
				$key = ':'.$id;
				break; //stop loop
			}
		}
		
		$hov_nummer_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('HOV_nummer_First');
		$begindatum_overeenkomst_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('Begindatum_HOV');
		$einddatum_overeenkomst_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('Einddatum_HOV');
		$vge_nummer_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('VGE_nummer_First');
		$vge_adres_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('VGE_adres_First');
		$naam_op_overeenkomst_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('Correspondentienaam_First');
		
		/*
		 * huurovereenkomst aanmaken
		*/
		$customparms['version'] = 3;
		$customparms['entity_id'] = $huis_id;
		$customparms['custom_'.$hov_nummer_field['id'].$key] = $hov_nummer;
		if (isset($start_date) && !empty($start_date)) {
			$customparms['custom_'.$begindatum_overeenkomst_field['id'].$key] = $start_date;
		}
		if (isset($end_date) && !empty($end_date)) {
			$customparms['custom_'.$einddatum_overeenkomst_field['id'].$key] = $end_date;
		}
		if (isset($inparms['vge_nummer'])) {
			$customparms['custom_'.$vge_nummer_field['id'].$key] = trim($inparms['vge_nummer']);
		}
		if (isset($inparms['vge_adres'])) {
			$customparms['custom_'.$vge_adres_field['id'].$key] = trim($inparms['vge_adres']);
		}
		if (isset($inparms['corr_name'])) {
			$customparms['custom_'.$naam_op_overeenkomst_field['id'].$key] = trim($inparms['corr_name']);
		}
		$res_custom = civicrm_api('CustomValue', 'Create', $customparms);
		if (civicrm_error($res_custom)) {
			return civicrm_api3_create_error($res_custom['error_message']);
		}
		$outparms['is_error'] = 0;
		
		//update correspondentie naam bij huishouden
		if (isset($inparms['corr_name'])) {
			$cor_parms['version'] = 3;
			$cor_parms['name'] = trim($inparms['corr_name']);
			$cor_parms['contact_id'] = $huis_id;
			$res_cor = civicrm_api('Contact', 'Create', $cor_parms);
		}
		
		/*
		 * if hh_persoon passed, check if relation hoofdhuurder or medehuurder
		* already exists between persoon and huishouden.
		*/
		if (isset($hh_persoon)) {
			$rel_hfd_id = CRM_Utils_DgwApiUtils::retrieveRelationshipTypeIdByNameAB('Hoofdhuurder');
			$parms = array(
					'version' => 3,
					'relationship_type_id' => $rel_hfd_id,
					'contact_id_a' => $huis_id,
			);
			$res = civicrm_api('Relationship', 'get', $parms);
			$updated = false;
			if (!civicrm_error($res)) {
				foreach($res['values'] as $rid => $value) {
					$rel_params['version'] = 3;
					$rel_params['id'] = $rid;
					$rel_params['relationship_type_id'] = $rel_hfd_id;
					if (isset($hh_start_date) && !empty($hh_start_date)) {
						$rel_params['start_date'] = $hh_start_date;
					}
					if (isset($hh_end_date) && !empty($hh_end_date)) {
						$rel_params['end_date'] = $hh_end_date;
					}
					civicrm_api('Relationship', 'Create', $rel_params);
					$updated = true;
				}
			}
		}

		/*
		 * if mh_persoon passed, check if relation hoofdhuurder or medehuurder
		 * already exists between persoon and huishouden.
		 */
		if (isset($mh_persoon)) {
			$rel_med_id = CRM_Utils_DgwApiUtils::retrieveRelationshipTypeIdByNameAB('Medehuurder');
			$rel_hfd_id = CRM_Utils_DgwApiUtils::retrieveRelationshipTypeIdByNameAB('Hoofdhuurder');
			$parms = array(
					'version' => 3,
					'relationship_type_id' => $rel_med_id,
					'contact_id_a' => $mh_persoon,
			);
			$res = civicrm_api('Relationship', 'get', $parms);
			$updated = false;
			if (!civicrm_error($res)) {
				foreach($res['values'] as $rid => $value) {
					$rel_params['version'] = 3;
					$rel_params['id'] = $rid;
					if (isset($mh_start_date) && !empty($mh_start_date)) {
						$rel_params['start_date'] = $mh_start_date;
					}
					if (isset($mh_end_date) && !empty($mh_end_date)) {
						$rel_params['end_date'] = $mh_end_date;
					}
					civicrm_api('Relationship', 'Create', $rel_params);
					$updated = true;
				}
			}
		}
	}
	$outparms['is_error'] = "0";
	return $outparms;
}