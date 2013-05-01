<?php
/*
 +--------------------------------------------------------------------+
 | De Goede Woning CiviCRM  Specifieke Contact API, gebaseerd op      |
 |                          standaard CiviCRM Contact API             |
 |                          Beschreven in 'Detailontwerp API ophalen  |
 |                          contact uit CiviCRM.doc'                  |
 | Copyright (C) 2010 Erik Hommel and De Goede Woning                 |
 | Licensed to CiviCRM under the Academic Free License version 3.0.   |
 +--------------------------------------------------------------------+
 | This file is based on CiviCRM, and owned by De Goede Woning        |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 | Author   :   Erik Hommel (EE-atWork, hommel@ee-atwork.nl           |
 |                  www.ee-atwork.nl)                                 |
 | Date     :   28 October 2010                                       |
 | Project  :   Implementation CiviCRM at De Goede Woning             |
 | Descr.   :   Specific versions wrapped around standard API's to    |
 |              facilitate synchronization of data between different  |
 |              systems and website. At time of creation, called by   |
 |              the CiviCRM REST interface                            |
 +--------------------------------------------------------------------+
 | Incident 19 06 12 004 Add custom data to household for             |
 | dgwcontact_get function                                            |
 |                                                                    |
 | Author	:	Erik Hommel (EE-atWork, hommel@ee-atwork.nl)  |
 | Date		:	19 June 2012                                  |
 +--------------------------------------------------------------------+
 | Incident 05 02 13 003 Organization name always empty when synced   |
 |                       from First. Check for org name               |
 | Author	:	Erik Hommel (erik.hommel@civicoop.org)        |
 | Date		:	07 Feb 2013                                   |
 +--------------------------------------------------------------------+
 */

/**
 * @Todo config bestand en constanten goed zetten
 */
#require_once 'dgwConfig.php';
require_once 'DgwPhone.php';
require_once 'DgwEmail.php';
require_once 'DgwGroup.php';
require_once 'DgwTag.php';
require_once 'DgwAddress.php';
require_once 'DgwRelationship.php';
require_once 'DgwNote.php';
require_once 'DgwFirstsync.php';

/*
 * Function to get details of a contact
 */
function civicrm_api3_dgwcontact_get($inparms) {
	
    /*
     * initialize output array
     */
    $outparms = array("");

    /**
     * array to hold all possible input parms
     *
     * @Todo create a spec function for this api
     */
    $valid_input = array("contact_id", "persoonsnummer_first", "achternaam",
        "geboortedatum", "bsn", "contact_type");

    /*
     * check if input parms hold at least one valid parameter
     */
    $valid = false;
    foreach ($valid_input as $validparm) {
        if (isset($inparms[$validparm])) {
            $valid = true;
        }
    }
    if (!$valid) {
        return civicrm_api3_create_error( 'Geen geldige input parameters voor
            dgwcontact_get' );
    }

    /*
     * only if valid parameters used
     */
    if ($valid) {

        /*
         * standard API returns default 25 rows. For DGW changed here: if no
         * rowCount passed, default = 100
         */
        if (isset($inparms['rowCount'])) {
            $rowCount = $inparms['rowCount'];
        } else {
            $rowCount = 100;
        }
        
        $civiparms1 = array(
        	"rowCount"   => $rowCount,
        	"version"    => 3
        );

        /*
         * if contact_id entered, no further parms needed
         */
        if (isset($inparms['contact_id'])) {
            $civiparms1['contact_id'] = $inparms['contact_id'];
            $civires1 = civicrm_api('Contact', 'get', $civiparms1);
        } elseif (isset($inparms['persoonsnummer_first'])) {
            /*
             * if persoonsnummer_first entered, no further parms needed
             * (issue 240 ook voor organisatie)
             */
        	$civiparms1['contact_type'] = 'Individual';
        	/**
        	 * @TodO replace constant CFPERSNR with config value
        	 */
        	$civiparms1[CFPERSNR] = $inparms['persoonsnummer_first'];
        	$civires1 = civicrm_api('Contact', 'get', $civiparms1);
            if (key($civires1) == null) {
            	unset($civiparms1[CFPERSNR]);
            	$inparms['contact_type'] = "Organization";
            	$civiparms1['contact_type'] = 'Organization';
            	/**
            	 * @TodO replace constant CFORGPERSNR with config value
            	 */
            	$civiparms1[CFORGPERSNR] = $inparms['persoonsnummer_first'];
            	$civires1 = civicrm_api('Contact', 'get', $civiparms1);
            } 
        } else {
        	if (isset($inparms['bsn']) && !empty($inparms['bsn'])) {
        		/**
        		 * @TodO replace constant CFPERSBSN with config value
        		 */
        		$civiparms1[CFPERSBSN] = $inparms['bsn'];
        	} 
        	if (isset($inparms['achternaam']) && !empty($inparms['achternaam'])) {
        		$civiparms1['last_name'] = trim($inparms['achternaam']);
        	}
        	if (isset($inparms['geboortedatum']) && !empty($inparms['geboortedatum'])) {
        		$civiparms1['birth_date'] = $inparms['geboortedatum'];
        	}
        	if (isset($inparms['contact_type']) && !empty($inparms['contact_type'])) {
        		$civiparms1['contact_type'] = $inparms['contact_type'];
        	}
			
            $civires1 = civicrm_api('Contact', 'get', $civiparms1);
        }

        /*
         * check results from civicrm_contact_get, if error return error
         */
        if (civicrm_error($civires1)) {
            return civicrm_api3_create_error($civires1['error_message']);
        } else {
            /*
             * if no error, set contact part of output parms. Result could
             * contain more contacts, so for each contact in $civires
             */
            $i = 1;
            foreach ($civires1['values'] as $result) {
                $contact_id = $result['contact_id'];
                
                $data = $result;
                
                //retrieve custom values for contact
                $customvalues = CRM_Utils_DgwApiUtils::retrieveCustomValuesForContact($data);
                if ($customvalues['is_error'] == '0') {
                	foreach($customvalues['values'] as $value) {
                		if (isset($value['normalized_value'])) {
                			$data[$value['name'].'_id'] = $value['value'];
                			$data[$value['name']] = $value['normalized_value'];
                		} else {
                			$data[$value['name']] = $value['value'];
                		}
                	}
                }
                
                /*
                 * incident 20 11 12 002 retrieve is_deleted for contact
                */
                $data['is_deleted'] = $data['contact_is_deleted'];
                unset($data['contact_is_deleted']);
                
                /*
                 * vanaf CiviCRM 3.3.4 website in aparte tabel
                * en niet meer in standaard API
                */
                $civires4 = civicrm_api('Website', 'get', array(
                		'version' => 3,
                		'contact_id' => $contact_id
                ));
                if ($civires4['is_error'] == '0' && isset($civires4['values']) && is_array($civires4['values']) && count($civires4['values'])) {
                	$website = reset($civires4['values']);
                	$data['home_URL'] = $website['url'];
                }
                
                $outparms[$i] = $data;
                
                $i++;
            }
        }
    }
    $outparms[0]['record_count'] = ($i - 1);
    return ($outparms);
}
/*
 * Function to update an individual emailaddress in CiviCRM
 * incoming is either email_id or cde_refno
 */
function civicrm_api3_dgwcontact_emailupdate($inparms) {
    /*
     * if no email_id or cde_refno passed, error
     */
    if (!isset($inparms['email_id']) && !isset($inparms['cde_refno'])) {
        return civicrm_create_error("Email_id en cde_refno ontbreken beiden");
    }
    if (isset($inparms['email_id'])) {
        $email_id = trim($inparms['email_id']);
    } else {
        $email_id = null;
    }
    if (isset($inparms['cde_refno'])) {
        $cde_refno = trim($inparms['cde_refno']);
    } else {
        $cde_refno = null;
    }
    if (empty($email_id) && empty($cde_refno)) {
        return civicrm_create_error("Email_id en cde_refno ontbreken beiden");
    }
    /*
     * if start_date passed and format invalid, error
     */
    if (isset($inparms['start_date']) && !empty($inparms['start_date'])) {
        $valid_date = checkDateFormat($inparms['start_date']);
        if (!$valid_date) {
            return civicrm_create_error("Onjuiste formaat start_date");
        } else {
          $start_date = $inparms['start_date'];
        }
    }
    /*
     * if end_date passed and format invalid, error
     */
    if (isset($inparms['end_date']) && !empty($inparms['end_date'])) {
        $valid_date = checkDateFormat($inparms['end_date']);
        if (!$valid_date) {
            return civicrm_create_error("Onjuiste formaat end_date");
        } else {
          $end_date = $inparms['end_date'];
        }
    }
    /*
     * if $cde_refno is used, retrieve email_id from synchronisation First table
     */
    if (!empty($cde_refno)) {
        $query = "SELECT ".FLDSYNCID." FROM ".TABSYNC." WHERE ".FLDSYNCKEY."
            = '$cde_refno' AND ".FLDSYNCENT." = 'email'";
        $daoSync = CRM_Core_DAO::executeQuery($query);
        $fldsyncid = FLDSYNCID;
        while ($daoSync->fetch()) {
            $email_id = $daoSync->$fldsyncid;
        }
    }
    /*
     * if $email_id is still empty, error
     */
    if (empty($email_id)) {
        return civicrm_create_error("Email niet gevonden");
    }
    /*
     * check if email exists in CiviCRM
     */
    $checkparms = array("email_id" => $email_id);
    $res_check = civicrm_api3_dgwcontact_emailget($checkparms);
    if (civicrm_error($res_check)) {
        return civicrm_create_error("Email niet gevonden");
    }
    /*
     * if location_type is invalid, error
     */
    if (isset($inparms['location_type'])) {
        $location_type_id = (int) getLocationTypeId($inparms['location_type']);
        if ($location_type_id == "") {
            return civicrm_create_error("Location_type is ongeldig");
        } else {
            $location_type = strtolower(trim($inparms['location_type']));
        }
    }
    /*
     * if is_primary is not 0 or 1, error
     */
    if (isset($inparms['is_primary'])) {
        if ($is_primary != 0 && $is_primary == 1) {
            return civicrm_create_error("Is_primary is ongeldig");
        }
    }
    /*
     * if start_date > today and location_type is not toekomst, error
     */
    if (isset($start_date) && !empty($start_date)) {
        $start_date = date("Ymd", strtotime($start_date));
        if ($start_date > date("Ymd") && $location_type != "toekomst") {
            return civicrm_create_error("Combinatie location_type en
                start/end_date ongeldig");
        }
        /*
         * if location_type = toekomst and start_date is not > today, error
         */
        if ($location_type == "toekomst" && $start_date <= date("Ymd")) {
            return civicrm_create_error("Combinatie location_type en start/end_date
                ongeldig");
        }
    }
    /*
     * if end_date < today and location_type is not oud, error
     */
    if (isset($end_date) && !empty($end_date)) {
        $end_date = date("Ymd", strtotime($end_date));
        if ($end_date < date("Ymd") && $location_type != "oud") {
            return civicrm_create_error("Combinatie location_type en
                start/end_date ongeldig");
        }
        /*
         * if location_type = oud and end_date is empty or > today, error
         */
        if ($location_type == "oud") {
            if (empty($end_date) || $end_date > date("Ymd")) {
                return civicrm_create_error("Combinatie location_type en start/
                    end_date ongeldig");
            }
        }
    }
    /*
     * all validation passed, first retrieve email to get all current values
     * for total update record
     */
    $qry1 = "SELECT * FROM civicrm_email WHERE id = $email_id";
    $daoEmailCurrent = CRM_Core_DAO::executeQuery($qry1);
    $daoEmailCurrent->fetch();
    if (!isset($location_type_id)) {
        $location_type_id = $daoEmailCurrent->location_type_id;
    }
    if (!isset($inparms['email'])) {
        $email = $daoEmailCurrent->email;
    } else {
        $email = trim($inparms['email']);
    }
    $contactID = $daoEmailCurrent->contact_id;
    /*
     * issue 178: if email empty, delete email
     */
    if (empty($email)) {
		$delqry = "DELETE FROM civicrm_email WHERE id = $email_id";
		CRM_Core_DAO::executeQuery($delqry);
		$delqry = "DELETE FROM ".TABSYNC." WHERE ".FLDSYNCID." = $email_id";
		CRM_Core_DAO::executeQuery($delqry);

	} else {

		/*
		 * if is_primary = 1, set all existing records for contact to is_primary 0
		 */
		if ($is_primary == 1) {
			$qry2 = "UPDATE civicrm_email SET is_primary = 0 WHERE 
				contact_id = $contactID";
			$daoPrimUpd = CRM_Core_DAO::executeQuery($qry2);
		} else {
			$is_primary = 0;
		}
		/*
		 * update email with new values
		 */
		$qry3 = "UPDATE civicrm_email SET location_type_id = $location_type_id,
			is_primary = $is_primary, email = '$email' WHERE id = $email_id";
		$daoEmailUpd = CRM_Core_DAO::executeQuery($qry3);
		/*
		 * issue 158: if the email belongs to a hoofdhuurder, update the household
		 * email too
		 */
		$qry4 = "SELECT contact_id FROM civicrm_email WHERE id = $email_id";
		$res4 = CRM_Core_DAO::executeQuery($qry4);
		if ($res4->fetch()) {
			if (isset($res4->contact_id)) {
				$huishoudenID = is_hoofdhuurder($res4->contact_id);
				if ($huishoudenID != 0) {
					/*
					 * update huishouden email if there is one, if not create
					 */
					$qry5 = "SELECT count(*) AS aantal FROM civicrm_email WHERE
						contact_id = $huishoudenID";
					$res5 = CRM_Core_DAO::executeQuery($qry5);
					if ($res5->fetch()) {
						if ($res5->aantal == 0) {
							$qry6 = "INSERT INTO civicrm_email SET contact_id =
								$huishoudenID, location_type_id = $location_type_id";
						} else {
							$qry6 = "UPDATE civicrm_email SET location_type_id =
								$location_type_id";
						}
					}
					$qry6 .= ", is_primary = $is_primary, email = '$email'";
					if (substr($qry6, 0, 6) == "UPDATE") {
						$qry6 = $qry6." WHERE contact_id = $huishoudenID";
					}
					CRM_Core_DAO::executeQuery($qry6);
				}
			}
		}
		/*
		 * set new cde_refno in synctable if passed
		 */
		if (isset($inparms['cde_refno']) && !empty($inparms['cde_refno'])) {
			$refno = trim($inparms['cde_refno']);
			$upd_address = "UPDATE ".TABSYNC." SET ".FLDSYNCKEY." = $refno WHERE "
				.FLDSYNCID." = $email_id";
			CRM_Core_DAO::executeQuery($upd_address);
		}
	}
	/*
	 * issue 239: if there is only one email left, make this primary
	 */
	$checkqry = "SELECT COUNT(id) AS aantal, is_primary FROM civicrm_email
		WHERE contact_id = $contactID";
	$daoCheckAantal = CRM_Core_DAO::executeQuery($checkqry);
	if ($daoCheckAantal->fetch()) {
		if ($daoCheckAantal->aantal == 1 &&
				$daoCheckAantal->is_primary == 0) {
			$updPrimary = "UPDATE civicrm_email SET is_primary = 1 WHERE
				contact_id = $contactID";
			CRM_Core_DAO::executeQuery($updPrimary);
		}
	}
	if (!empty($huishoudenID)) {
		$checkqry = "SELECT COUNT(id) AS aantal, is_primary FROM civicrm_email
			WHERE contact_id = $huishoudenID";
		$daoCheckAantal = CRM_Core_DAO::executeQuery($checkqry);
		if ($daoCheckAantal->fetch()) {
			if ($daoCheckAantal->aantal == 1 &&
					$daoCheckAantal->is_primary == 0) {
				$updPrimary = "UPDATE civicrm_email SET is_primary = 1 WHERE
					contact_id = $huishoudenID";
				CRM_Core_DAO::executeQuery($updPrimary);
			}
		}
	}
    $outparms['is_error'] = "0";
    return $outparms;

}
/*
 * Function to create new contact
 */
function civicrm_api3_dgwcontact_create($inparms) {
    /*
     * If contact_type passed and not valid, error. Else set contact_type
     * to default 'Individual'
     */
    if (isset($inparms['contact_type'])) {
        $contact_type = trim(ucfirst(strtolower($inparms['contact_type'])));
        if ($contact_type != "Individual" && $contact_type != "Household"
                && $contact_type != "Organization") {
            return civicrm_create_error("Ongeldig contact_type $contact_type");
        }
    } else {
        $contact_type = "Individual";
    }

    /*
     * If type is not Individual, name is mandatory
     */
    if ($contact_type != "Individual") {
        if (!isset($inparms['name'])) {
            return civicrm_create_error("Geen first_name/last_name of name
                gevonden");
        } else {
            $name = trim($inparms['name']);
        }
        if (empty($name)) {
            return civicrm_create_error("Geen first_name/last_name of name
                gevonden");
        }
    }

    /*
     * If type is Individual, a number of checks need to be done
     */
    if ($contact_type == "Individual") {
        /*
         * first and last name are mandatory
         * issue 85: not for First org (gender = 4)
         */
        if (!isset($inparms['first_name']) && $inparms['gender_id'] != 4) {
            return civicrm_create_error("Geen first_name/last_name of name
                gevonden");
        } else {
            $first_name = trim($inparms['first_name']);
        }
        if (empty($first_name) && $inparms['gender_id'] != 4) {
            return civicrm_create_error("Geen first_name/last_name of name
                gevonden");
        }
        if (!isset($inparms['last_name'])) {
            return civicrm_create_error("Geen first_name/last_name of name
                gevonden");
        } else {
            $last_name = trim($inparms['last_name']);
        }
        if (empty($last_name)) {
            return civicrm_create_error("Geen first_name/last_name of name
                gevonden");
        }
        /*
         * gender_id has to be valid if entered. If not entered, use default
         */
        if (isset($inparms['gender_id'])) {
            $gender_id = trim($inparms['gender_id']);
            if ($gender_id != 1 && $gender_id != 2 && $gender_id != 3
                    && $gender_id != 4) {
                return civicrm_create_error("Gender_id is ongeldig");
            }
        } else {
            $gender_id = 3;
        }
        /*
         * issue 149: if gender = 4, persoonsnummer first has to be passed
         */
        if ($gender_id == 4) {
            if (!isset($inparms['persoonsnummer_first']) ||
                    empty($inparms['persoonsnummer_first'])) {
                return civicrm_create_error("Gender_id 4 mag alleen als
                    persoonsnummer first ook gevuld is");
            }
        }
        /*
         * BSN will have to pass 11-check if entered
         */
        if (isset($inparms['bsn'])) {
            $bsn = trim($inparms['bsn']);
            if (!empty($bsn)) {
                $bsn_valid = validateBsn($bsn);
                if (!$bsn_valid) {
                    return civicrm_create_error("Bsn voldoet niet aan 11-proef");
                }
            }
        }
        /*
         * if birth date is entered, format has to be valid
         */
        if (isset($inparms['birth_date']) && !empty($inparms['birth_date'])) {
            $valid_date = checkDateFormat($inparms['birth_date']);
            if (!$valid_date) {
                return civicrm_create_error("Onjuiste formaat birth_date");
            } else {
                $birth_date = $inparms['birth_date'];
            }
        } else {
            $birth_date = "";
        }
        /*
         * if individual already exists with persoonsnummer_first, error
         */
        if (isset($inparms['persoonsnummer_first'])) {
            $pers_first = trim($inparms['persoonsnummer_first']);
            $qry = "SELECT count(id) as aantal FROM ".TABFIRSTPERS." WHERE ".
                FLDPERSNR." = 'pers_first'";
            $daoPers = CRM_Core_DAO::executeQuery($qry);
            while ($daoPers->fetch()) {
                $aantal = $daoPers->aantal;
            }
            if ($aantal > 0) {
                return civicrm_create_error("Persoon bestaat al");
            }
        }
        /*
         * if burg_staat entered and invalid, error
         */
        if (isset($inparms['burg_staat_id'])) {
            $check = getOptionValue("Burgerlijke staat", "",
                    $inparms['burg_staat_id']);
            if ($check == "error") {
                return civicrm_create_error("Burg_staat_id is ongeldig");
            } else {
                $burg_staat_id = $inparms['burg_staat_id'];
            }
        }
        /*
         * if huidige woonsituatie entered, explode and if any value invalid,
         * error
         */
        if (isset($inparms['huidige_woonsituatie'])) {
            $values = explode(",", $inparms['huidige_woonsituatie']);
            $teller = 0;
            $huidige_woonsit = null;
            foreach ($values as $value) {
                if (!empty($value)) {
                    $check = getOptionValue("Huidige woonsituatie", "", $value, "");
                        if ($check == "error") {
                        return civicrm_create_error("Huidige woonsituatie is
                            ongeldig");
                    } else {
                        $huidige_woonsit = $huidige_woonsit.$value.
                            CRM_Core_DAO::VALUE_SEPARATOR;
                       $teller++;
                    }
                }
            }
        }
        if ($teller > 0) {
            $huidige_woonsit = CRM_Core_DAO::VALUE_SEPARATOR.$huidige_woonsit;
        }
        /*
         * if hoofdhuurder entered, only 0 or 1 are allowed
         */
        if (isset($inparms['hoofdhuurder'])) {
            $hoofdhuurder = (int) trim($inparms['hoofdhuurder']);
            if ($hoofdhuurder != 0 and $hoofdhuurder != 1) {
                return civicrm_create_error("Hoofdhuurder is ongeldig");
            }
        }
        /*
         * if andere corporatie entered, error if invalid
         */
        if (isset($inparms['andere_corporatie'])) {
            $andere_corp = (int) trim($inparms['andere_corporatie']);
            $check = getOptionValue("Corporatie", "", $andere_corp);
            if ($check == "error") {
                return civicrm_create_error("Andere corporatie is ongeldig");
            }
        }
        /*
         * if bruto jaarinkomen entered, only empty or numeric allowed
         */
        if (isset($inparms['bruto_jaarinkomen'])) {
            $bruto_jaarinkomen = trim($inparms['bruto_jaarinkomen']);
            if (empty($bruto_jaarinkomen)) {
                $bruto_jaarinkomen = 0;
            }
            if (!is_numeric($bruto_jaarinkomen)) {
                return civicrm_create_error("Bruto jaarinkomen heeft ongeldige
                    tekens");
            }
        }
        /*
         * if huishoudgrootte entered, error if invalid
         */
        if (isset($inparms['huishoudgrootte'])) {
            $huishoudgrootte = (int) trim($inparms['huishoudgrootte']);
            $check = getOptionValue("Huishoudgrootte", "", $huishoudgrootte);
            if ($check == "error") {
                return civicrm_create_error("Huishoudgrootte is ongeldig");
            }
        }
        /*
         * if aanbod bekend entered, explode and if any value invalid,
         * error
         */
        if (isset($inparms['aanbod_bekend'])) {
            $aanbod_bekend = null;
            $teller = 0;
            $values = explode(",", $inparms['aanbod_bekend']);
            foreach ($values as $value) {
                if (!empty($value)) {
                    $check = getOptionValue("Bekend met koopaanbod", "", $value);
                    if ($check == "error") {
                        return civicrm_create_error("Aanbod bekend is ongeldig");
                    } else {
                        $aanbod_bekend = CRM_Core_DAO::VALUE_SEPARATOR.
                            $aanbod_bekend.$value;
                        $teller++;
                    }
                }
            }
            if ($teller > 0) {
                $aanbod_bekend = $aanbod_bekend.CRM_Core_DAO::VALUE_SEPARATOR;
            }
        }
        /*
         * if particulier entered, only  0 or 1 are allowed
         */
        if (isset($inparms['particulier'])) {
            $particulier = (int) trim($inparms['particulier']);
            if ($particulier != 0 and $particulier != 1) {
                return civicrm_create_error("Particulier is ongeldig");
            }
        }
        /*
         * if woonkeusdatum is entered, format has to be valid
         */
        if (isset($inparms['woonkeusdatum'])) {
            $valid_date = checkDateFormat($inparms['woonkeusdatum']);
            if (!$valid_date) {
                return civicrm_create_error("Onjuiste formaat woonkeusdatum");
            } else {
                $woonkeusdatum = $inparms['woonkeusdatum'];
            }
        }
    }
    /*
     * If we get here, all validation has been succesful. Now first the
     * CiviCRM contact can be created. First set parameters based on
     * contact type
     *
     * Issue 149: gender_id 4 means organization has to be set with
     * persoonsnummer first and name as concat(first, middle and last) name
     */
    $middle_name = trim($inparms['middle_name']);
    if ($gender_id == 4) {
        $contact_type = "Organization";
        $name = null;
        if (isset($first_name) && !empty($first_name)) {
            $name = $first_name;
        }
        if (isset($middle_name) && !empty($middle_name)) {
           if (empty($name)) {
               $name = $middle_name;
           } else {
               $name .= " ".$middle_name;
           }
        }
        if (isset($last_name) && !empty($last_name)) {
            if (empty($name)) {
                $name = $last_name;
            } else {
                $name .= " ".$last_name;
            }
        }

    }
    switch ($contact_type) {
        case "Household":
            $civiparms = array(
                "contact_type"      =>  "Household",
                "household_name"    =>  $name);
            break;
        case "Organization":
            if (isset($inparms['home_url'])) {
                $homeURL = trim(stripslashes($inparms['home_url']));
            } else {
                $homeURL = "";
            }
            if (isset($inparms['kvk_nummer'])) {
                $kvk_nummer = trim($inparms['kvk_nummer']);
            } else {
                $kvk_nummer = "";
            }
            $civiparms = array(
                "contact_type"      =>  "Organization",
                "organization_name" =>  $name,
                "home_url"          =>  $homeURL,
                "sic_code"          =>  $kvk_nummer);
            break;
        case "Individual":
            if (isset($inparms['middle_name'])) {
                $middle_name = trim($inparms['middle_name']);
            } else {
                $middle_name = "";
            }
            $civiparms = array(
                "contact_type"      =>  "Individual",
                "first_name"        =>  $first_name,
                "last_name"         =>  $last_name,
                "middle_name"       =>  $middle_name,
                "gender_id"         =>  $gender_id,
                "show_all"          =>  $inparms['show_all']);

            if (isset($birth_date) && !empty($birth_date)) {
                $civiparms['birth_date'] = date("Ymd", strtotime($birth_date));
            }
            break;
    }
    /*
     * use standard API to create CiviCRM contact
     */
    $create_contact = civicrm_contact_create($civiparms);
    /*
     * if error, return error
     */
    if (civicrm_error($create_contact)) {
        $errmsg = $create_contact['error_message'];
        return civicrm_create_error("Onverwachte fout, contact kon niet
            aangemaakt worden in CiviCRM. Melding van CiviCRM : $errmsg");
    } else {
        $contact_id = $create_contact['contact_id'];
        /*
         * create custom data for Individual
         */
        if ($contact_type == "Individual") {
           /*
            * create array with required data, minimal is contact_id
            */
            $customparms['entityID'] = $contact_id;
            /*
             * add custom fields if entered
             */
            if (isset($pers_first)) {
                $customparms[CFPERSNR] = $pers_first;
            }
            if (isset($bsn)) {
                $customparms[CFPERSBSN] = $bsn;
            }
            if (isset($burg_staat_id)) {
                $customparms[CFPERSBURG] = $burg_staat_id;
            }
            if (isset($saldo)) {
                $customparms[CFPERSTOT] = $saldo;
            }
            if (isset($inparms['woonkeusnummer'])) {
                $customparms[CFWKNR] = trim($inparms['woonkeusnummer']);
            }
            if (isset($woonkeusdatum) && !empty($woonkeusdatum)) {
                $customparms[CFWKDAT] = date("Ymd", strtotime($woonkeusdatum));
            }
            if (isset($huidige_woonsit)) {
                $customparms[CFWKSIT] = $huidige_woonsit;
            }
            if (isset($hoofdhuurder)) {
                $customparms[CFWKHOOFD] = $hoofdhuurder;
            }
            if (isset($andere_corp)) {
                $customparms[CFWKCORP] = $andere_corp;
            }
            if (isset($bruto_jaarinkomen)) {
                $customparms[CFWKBRUTO] = $bruto_jaarinkomen;
            }
            if (isset($huishoudgrootte)) {
                $customparms[CFWKHUIS] = $huishoudgrootte;
            }
            if (isset($aanbod_bekend)) {
                $customparms[CFWKHOE] = $aanbod_bekend;
            }
            if (isset($particulier)) {
                $customparms[CFWKPART] = $particulier;
            }
            /*
             * following fields have to be entered for the synchronization of
             * contact, address, email and phone if persoonsnummer first has
             * been entered. Contain key values from First
             */
            if (isset($pers_first) && !empty($pers_first)) {
                $customparms[CFSYNCACT] = "none";
                $customparms[CFSYNCENT] = "contact";
                $customparms[CFSYNCID] = $contact_id;
                $customparms[CFSYNCKEY] = $pers_first;
            }
            /*
             * add custom fields to CiviCRM if there are any
             */
            if (!empty($customparms)) {
                require_once("CRM/Core/BAO/CustomValueTable.php");
                $customres = CRM_Core_BAO_CustomValueTable::setValues(
                        $customparms);
            }
        }
        /*
         * create custom data for Organization
         */
        if ($contact_type == "Organization") {
            /*
             * add custom fields if entered
             */
            if ($gender_id == 4) {
                $customparms[CFORGPERSNR] = $pers_first;
                $customparms['entityID'] = $contact_id;
                $customparms[CFSYNCACT] = "none";
                $customparms[CFSYNCENT] = "contact";
                $customparms[CFSYNCID] = $contact_id;
                $customparms[CFSYNCKEY] = $pers_first;
                require_once("CRM/Core/BAO/CustomValueTable.php");
                $customres = CRM_Core_BAO_CustomValueTable::setValues(
                        $customparms);
            }
        }
        $outparms = array(
            "contact_id"    =>  $contact_id,
            "is_error"      =>  0);
    }
    return $outparms;
}
/*
 * Function to update contact
 */
function civicrm_api3_dgwcontact_update($inparms) {
    /*
     * if no contact_id or persoonsnummer_first passed, error
     */
    if (!isset($inparms['contact_id']) &&
            !isset($inparms['persoonsnummer_first'])) {
        return civicrm_create_error("Contact_id en persoonsnummer_first
            ontbreken beiden");
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
        return civicrm_create_error("Contact_id en persoonsnummer_first
            ontbreken beiden");
    }

    /*
     * contact has to exist in CiviCRM, either with contact_id or with
     * persoonsnummer_first. This needs to be checked with contact_id first,
     * because persoonsnummer_first can be passed when still empty in CiviCRM
     */
    if (isset($pers_nr) && !empty($pers_nr)) {
        if (!isset($contact_id) or empty($contact_id)) {
            $qry = "SELECT count(id) as aantal, entity_id FROM ".
                TABFIRSTPERS." WHERE ".FLDPERSNR." = '$pers_nr'";
            $daoPers = CRM_Core_DAO::executeQuery($qry);
            if ($daoPers->fetch()) {
                $aantal = $daoPers->aantal;
                $contact_id = $daoPers->entity_id;
            }
            if ($aantal == 0) {
				/*
				 * issue 240: controleer of contact als organisatie bekend
				 * is
				 */
				$qry = "SELECT count(id) as aantal, entity_id FROM ".
					TABFIRSTORG." WHERE ".FLDORGPERSNR." = '$pers_nr'";
				$daoOrg = CRM_Core_DAO::executeQuery($qry);
				if ($daoOrg->fetch()) {
					$aantal = $daoOrg->aantal;
					$contact_id = $daoOrg->entity_id;
				}
				if ($aantal == 0) {
					return civicrm_create_error("Contact niet gevonden");
				}
            }
        }
    }
    $qry = "SELECT count(id) as aantal, contact_type FROM civicrm_contact
        WHERE id = $contact_id";
    $daoPers = CRM_Core_DAO::executeQuery($qry);
    while ($daoPers->fetch()) {
        $aantal = $daoPers->aantal;
        $contact_type = $daoPers->contact_type;
    }
    if ($aantal == 0) {
        return civicrm_create_error("Contact niet gevonden");
    }
    /*
     * gender_id has to be valid if entered. 
     */
    if (isset($inparms['gender_id'])) {
        $gender_id = trim($inparms['gender_id']);
        if ($gender_id != 1 && $gender_id != 2 && $gender_id != 3 &&
                $gender_id != 4) {
            return civicrm_create_error("Gender_id is ongeldig");
        }
    }
    /*
     * issue 149: if contact type = organization and gender_id is not 4,
     * first_name, last_name and persoonsnummer_first have to be passed
     */
    if ($contact_type == "Organization" && isset($gender_id)) {
        if ($gender_id != 4) {
            if (!isset($inparms['first_name']) || !isset($inparms['last_name'])
                    || !isset($pers_nr)) {
                return civicrm_create_error("First name, last name en
                    persoonsnummer first moeten gevuld zijn als gewijzigd wordt
                    van organisatie naar persoon");
            }
            if (empty($inparms['first_name']) || empty($inparms['last_name']) ||
                    empty($pers_nr)) {
                return civicrm_create_error("First name, last name en
                    persoonsnummer first moeten gevuld zijn als gewijzigd wordt
                    van organisatie naar persoon");
            }
        }
    }
    /*
     * BSN will have to pass 11-check if entered
     */
    if (isset($inparms['bsn'])) {
        $bsn = trim($inparms['bsn']);
        if (!empty($bsn)) {
            $bsn_valid = validateBsn($bsn);
            if (!$bsn_valid) {
                return civicrm_create_error("Bsn voldoet niet aan 11-proef");
            }
        }
    }
    /*
     * if birth date is entered, format has to be valid
     */
    if ( isset( $inparms['birth_date'] ) && !empty( $inparms['birth_date'] ) ) {
        $valid_date = checkDateFormat($inparms['birth_date']);
        if (!$valid_date) {
            return civicrm_create_error("Onjuiste formaat birth_date");
        } else {
            $birth_date = $inparms['birth_date'];
        }
    } 
    /*
     * if burg_staat entered and invalid, error
     */
    if (isset($inparms['burg_staat_id'])) {
        $check = getOptionValue("Burgerlijke staat", "",
            $inparms['burg_staat_id']);
        if ($check == "error") {
            return civicrm_create_error("Burg_staat_id is ongeldig");
        } else {
            $burg_staat_id = $inparms['burg_staat_id'];
        }
    }
    /*
     * if huidige woonsituatie entered, explode and if any value invalid,
     * error
     */
    if (isset($inparms['huidige_woonsituatie'])) {
        $values = explode(",", $inparms['huidige_woonsituatie']);
        $teller = 0;
        $huidige_woonsit = null;
        foreach ($values as $value) {
            if (!empty($value)) {
                $check = getOptionValue("Huidige woonsituatie", "", $value, "");
                if ($check == "error") {
                    return civicrm_create_error("Huidige woonsituatie is ongeldig");
                } else {
                    $huidige_woonsit = $huidige_woonsit.$value.
                        CRM_Core_DAO::VALUE_SEPARATOR;
                   $teller++;
                }
            }
        }
        if ($teller > 0) {
            $huidige_woonsit = CRM_Core_DAO::VALUE_SEPARATOR.$huidige_woonsit;
        }
    }
    /*
     * if hoofdhuurder entered, only 0 or 1 are allowed
     */
    if (isset($inparms['hoofdhuurder'])) {
        $hoofdhuurder = (int) trim($inparms['hoofdhuurder']);
        if ($hoofdhuurder != 0 and $hoofdhuurder != 1) {
            return civicrm_create_error("Hoofdhuurder is ongeldig");
        }
    }
    /*
     * if andere corporatie entered, error if invalid
     */
    if (isset($inparms['andere_corporatie'])) {
        $andere_corp = (int) trim($inparms['andere_corporatie']);
        $check = getOptionValue("Corporatie", "", $andere_corp);
        if ($check == "error") {
            return civicrm_create_error("Andere corporatie is ongeldig");
        }
    }
    /*
     * if bruto jaarinkomen entered, only empty or numeric allowed
     */
    if (isset($inparms['bruto_jaarinkomen'])) {
        $bruto_jaarinkomen = trim($inparms['bruto_jaarinkomen']);
        if (empty($bruto_jaarinkomen)) {
            $bruto_jaarinkomen = 0;
        }
        if (!is_numeric($bruto_jaarinkomen)) {
            return civicrm_create_error("Bruto jaarinkomen heeft ongeldige
                tekens");
        }
    }
    /*
     * if huishoudgrootte entered, error if invalid
     */
    if (isset($inparms['huishoudgrootte'])) {
        $huishoudgrootte = (int) trim($inparms['huishoudgrootte']);
        $check = getOptionValue("Huishoudgrootte", "", $huishoudgrootte);
        if ($check == "error") {
            return civicrm_create_error("Huishoudgrootte is ongeldig");
        }
    }
    /*
     * if aanbod bekend entered, explode and if any value invalid,
     * error
     */
    if (isset($inparms['aanbod_bekend'])) {
        $values = explode(",", $inparms['aanbod_bekend']);
        $teller = 0;
        $aanbod_bekend = null;
        foreach ($values as $value) {
            if (!empty($value)) {
                $check = getOptionValue("Bekend met koopaanbod", "", $value, "");
                if ($check == "error") {
                    return civicrm_create_error("Aanbod bekend is ongeldig");
                } else {
                    $aanbod_bekend = $aanbod_bekend.$value.
                        CRM_Core_DAO::VALUE_SEPARATOR;
                   $teller++;
                }
            }
        }
        if ($teller > 0) {
            $aanbod_bekend = CRM_Core_DAO::VALUE_SEPARATOR.$aanbod_bekend;
        }
    }
    /*
     * if particulier entered, only  0 or 1 are allowed
     */
    if (isset($inparms['particulier'])) {
        $particulier = (int) trim($inparms['particulier']);
        if ($particulier != 0 and $particulier != 1) {
            return civicrm_create_error("Particulier is ongeldig");
        }
    }
    /*
     * if woonkeusdatum is entered, format has to be valid
     */
    if (isset($inparms['woonkeusdatum'])) {
        $valid_date = checkDateFormat($inparms['woonkeusdatum']);
        if (!$valid_date) {
            return civicrm_create_error("Onjuiste formaat woonkeusdatum");
        } else {
            $woonkeusdatum = $inparms['woonkeusdatum'];
        }
    }
    /*
     * If we get here, all validation has been succesful. If is_deleted is
     * 1, then contact has to be deleted
     */
    $custom_update = false;
    if (isset($inparms['is_deleted']) && $inparms['is_deleted'] == 1) {
        $civiparms = array("contact_id" => $contact_id);
        $res_del = civicrm_contact_delete($civiparms);
        if (civicrm_error($res_del)) {
            return civicrm_create_error("Contact kon niet verwijderd worden
                uit CiviCRM, melding : ".$res_del['error_message']);
        } else {
            $outparms['is_error'] = "0";
        }
    } else {
        /*
         * Contact needs to be updated in CiviCRM. Set parameters according to
         * contact_type
         *
         * Issue 149: gender_id 4 means organization has to be set with
         * persoonsnummer first and name as concat(first, middle and last) name
         */
        if ($gender_id == 4) {
            $contact_type = "Organization";
            /*
             * if organization name not passed, retrieve from civicrm_contact
             */
            if (!isset($inparms['name'])) {
                $qry = "SELECT first_name, middle_name, last_name, organization_name FROM
                    civicrm_contact WHERE id = $contact_id";
                $daoOrgName = CRM_Core_DAO::executeQuery($qry);
                if ($daoOrgName->fetch()) {
                    $inparms['name'] = "";
                    /*
                     * incident 05 02 13 003 lege naam bij update van organisaties.
                     * Snelle fix met organisatienaam om te voorkomen dat de naam
                     * leeg wordt terwijl er al een naam in de organisatie staat
                     */
                    if ( !empty( $daoOrgName->organization_name ) ) {
                        $inparms['name'] = $daoOrgName->organization_name;
                    } else {
                        if (!empty($daoOrgName->first_name)) {
                            $inparms['name'] = $daoOrgName->first_name;
                        }
                        if (!empty($daoOrgName->middle_name)) {
                            if (empty($inparms['name'])) {
                                $inparms['name'] = $daoOrgName->middle_name;
                            } else {
                                $inparms['name'] .= " ".$daoOrgName->middle_name;
                            }
                        }
                        if (!empty($daoOrgName->last_name)) {
                            if (empty($inparms['name'])) {
                                $inparms['name'] = $daoOrgName->last_name;
                            } else {
                                $inparms['name'] .= " ".$daoOrgName->last_name;
                            }
                        }
                    }
                } else {
                    $inparms['name'] = "";
                }
            }
            if (isset($pers_nr)) {
                $customparms[CFORGPERSNR] = $pers_nr;
                $custom_update = true;
            }
        }
        $civiparms['contact_id'] = $contact_id;
        $civiparms['contact_type'] = $contact_type;
        switch ($contact_type) {
            case "Household":
                if (isset($inparms['name'])) {
                    $civiparms['household_name'] = trim($inparms['name']);
                }
                break;
            case "Organization":
                /*
                 * issue 149: if current contact is Organization and gender in
                 * update is not 4, then organization has been changed to person
                 * in First. In that case, delete Organization and create
                 * Individual
                 */
                $orgtopers = false;
                if (isset($gender_id) && $gender_id != 4) {
                    $orgtopers = true;
                    $delparms['contact_id'] = $contact_id;
                    $delres = civicrm_contact_delete($delparms);
                    /*
                     * create individual with new values
                     */
                    $addparms['contact_type'] = "Individual";
                    if (isset($inparms['first_name'])) {
                        $addparms['first_name'] = trim($inparms['first_name']);
                    }
                    if (isset($inparms['middle_name'])) {
                        $addparms['middle_name'] = trim($inparms['middle_name']);
                    }
                    if (isset($inparms['last_name'])) {
                        $addparms['last_name'] = trim($inparms['last_name']);
                    }
                    if (isset($gender_id)) {
                        $addparms['gender_id'] = $gender_id;
                    } else {
                        $addparms['gender_id'] = 3;
                    }
                    if (isset($birth_date)) {
                        $addparms['birth_date'] = $birth_date;
                    }
                    $addres = civicrm_contact_create($addparms);
                    if (civicrm_error($addres)) {
                        return civicrm_create_error("Onverwachte fout - persoon
                            kon niet aangemaakt in dgwcontact_update voor
                            organisatie naar persoon - ".$addres['error_message']);
                    } else {
                        $contact_id = $addres['contact_id'];
                        /*
                         * create record in synctable
                         */
                        $customparms[CFSYNCACT] = "none";
                        $customparms[CFSYNCENT] = "contact";
                        $customparms[CFSYNCID] = $contact_id;
                        $customparms[CFSYNCKEY] = $pers_nr;
                    }
                } else {
                    if (isset($inparms['name'])) {
                        $civiparms['organization_name'] = trim($inparms['name']);
                    } else {
                        /*
                         * if name is empty or not set, check first, middle and
                         * last name
                         */
                        if (isset($inparms['first_name']) &&
                                !empty($inparms['first_name'])) {
                            $civiparms['organization_name'] =
                                trim($inparms['first_name']);
                        }
                        if (isset($inparms['middle_name']) &&
                                !empty($inparms['middle_name'])) {
                            $civiparms['organziation_name'] .=
                                trim($inparms['middle_name']);
                        }
                        if (isset($inparms['last_name']) &&
                                !empty($inparms['last_name'])) {
                            $civiparms['organization_name'] .=
                                trim($inparms['last_name']);
                        }
                    }
                    if (isset($inparms['home_url'])) {
                        $civiparms['home_url'] = trim($inparms['home_url']);
                    }
                    if (isset($inparms['ḱvk_nummer'])) {
                        $civiparms['sic_code'] = trim($inparms['kvk_nummer']);
                    }
                }
                break;
            case "Individual":
                if (isset($inparms['first_name'])) {
                    $civiparms['first_name'] = trim($inparms['first_name']);
                }
                if (isset($inparms['middle_name'])) {
                    $civiparms['middle_name'] = trim($inparms['middle_name']);
                }
                if (isset($inparms['last_name'])) {
                    $civiparms['last_name'] = trim($inparms['last_name']);
                }
                if (isset($gender_id)) {
                    $civiparms['gender_id'] = $gender_id;
                }
                if (isset($birth_date)) {
                    $civiparms['birth_date'] = $birth_date;
                }
                break;
        }
        /*
         * retrieve additional name parts if required because core API empties
         * non-passed parts (only for individual)!
         */
        if ($contact_type == "Individual") {
            if (!isset($civiparms['first_name']) || !isset($civiparms['middle_name'])
                    || !isset($civiparms['last_name'])) {
                $query = "SELECT first_name, last_name, middle_name FROM
                    civicrm_contact WHERE id = $contact_id";
                $daoContact = CRM_Core_DAO::executeQuery($query);
                while ($daoContact->fetch()) {
                    if (!isset($civiparms['first_name'])) {
                        $civiparms['first_name'] = $daoContact->first_name;
                    }
                    if (!isset($civiparms['middle_name'])) {
                        $civiparms['middle_name'] = $daoContact->middle_name;
                    }
                    if (!isset($civiparms['last_name'])) {
                        $civiparms['last_name'] = $daoContact->last_name;
                    }
                }
            }
        }
        /*
         * issue 149: update only if not from org to per situation
         */
        if (!$orgtopers) {
            $res_contact = civicrm_contact_update($civiparms);
            if (civicrm_error($res_contact)) {
                return civicrm_create_error("Onverwachte fout, contact $contact_id
                    kon niet bijgewerkt worden in CiviCRM, melding : ".
                    $res_contact['error_message']);
            }
        }
        /*
         * update custom data for individual if any of the custom fields
         * have been entered
         */
        $customparms['entityID'] = $contact_id;
        if (isset($pers_nr)) {
            $customparms[CFPERSNR] = $pers_nr;
            $custom_update = true;
        }
        if (isset($bsn)) {
            $customparms[CFPERSBSN] = $bsn;
            $custom_update = true;
        }
        if (isset($burg_staat_id)) {
            $custom_update = true;
            $customparms[CFPERSBURG] = $burg_staat_id;
        }
        if (isset($saldo)) {
            /*
             * check if saldo has changed. A job will run every night in First
             * that will send saldo for all contacts. Update only required
             * if value is not equal current value in database
             */
            $fldperstot = FLDPERSTOT;
            $select_saldo = "SELECT ".FLDPERSTOT." FROM ".TABFIRSTPERS." WHERE
                entity_id = $contact_id";
            $daoSaldo = CRM_Core_DAO::executeQuery($select_saldo);
            if ($daoSaldo->fetch()) {
                if ($daoSaldo->$fldperstot != $saldo) {
                $custom_update = true;
                $customparms[CFPERSTOT] = $saldo;
                }
            }
        }
        if (isset($inparms['woonkeusnummer'])) {
            $customparms[CFWKNR] = trim($inparms['woonkeusnummer']);
            $custom_update = true;
        }
        if (isset($woonkeusdatum)) {
            $customparms[CFWKDAT] = $woonkeusdatum;
            $custom_update = true;
        }
        if (isset($huidige_woonsit)) {
            $customparms[CFWKSIT] = $huidige_woonsit;
            $custom_update = true;
        }
        if (isset($hoofdhuurder)) {
            $customparms[CFWKHOOFD] = $hoofdhuurder;
            $custom_update = true;
        }
        if (isset($andere_corp)) {
            $customparms[CFWKCORP] = $andere_corp;
            $custom_update = true;
        }
        if (isset($bruto_jaarinkomen)) {
            $customparms[CFWKBRUTO] = $bruto_jaarinkomen;
            $custom_update = true;
        }
        if (isset($huishoudgrootte)) {
            $customparms[CFWKHUIS] = $huishoudgrootte;
            $custom_update = true;
        }
        if (isset($aanbod_bekend)) {
            $customparms[CFWKHOE] = $aanbod_bekend;
            $custom_update = true;
        }
        if (isset($particulier)) {
            $customparms[CFWKPART] = $particulier;
            $custom_update = true;
        }

        if ($custom_update) {
            require_once("CRM/Core/BAO/CustomValueTable.php");
            $customres = CRM_Core_BAO_CustomValueTable::setValues($customparms);
            /*
             * update records in synctable for contact with persoonsnummer_first
             * if not empty
             */
            if (!empty($pers_nr)) {
                $upd_contact = "UPDATE ".TABSYNC." SET ".FLDSYNCKEY." = $pers_nr
                    WHERE entity_id = $contact_id and ".FLDSYNCENT." = 'contact'";
                CRM_Core_DAO::executeQuery($upd_contact);
            }
        }
    }

        $outparms['is_error'] = "0";
        return $outparms;
}
/*
 * Function to update an individual address in CiviCRM
 * incoming is either address or adr_refno
 */
function civicrm_api3_dgwcontact_addressupdate($inparms) {
    /*
     * if no address_id or adr_refno passed, error
     */
    if (!isset($inparms['address_id']) && !isset($inparms['adr_refno'])) {
        return civicrm_create_error("Address_id en adr_refno ontbreken beiden");
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
        return civicrm_create_error("Address_id en adr_refno ontbreken beiden");
    }
    /*
     * if start_date passed and format invalid, error
     */
    if (isset($inparms['start_date']) && !empty($inparms['start_date'])) {
        $valid_date = checkDateFormat($inparms['start_date']);
        if (!$valid_date) {
            return civicrm_create_error("Onjuiste formaat start_date");
        } else {
          $start_date = $inparms['start_date'];
        }
    }
    /*
     * if end_date passed and format invalid, error
     */
    if (isset($inparms['end_date']) && !empty($inparms['end_date'])) {
        $valid_date = checkDateFormat($inparms['end_date']);
        if (!$valid_date) {
            return civicrm_create_error("Onjuiste formaat end_date");
        } else {
          $end_date = $inparms['end_date'];
        }
    }
    /*
     * if $adr_refno is used, retrieve address from synchronisation First table
     */
    if (!empty($adr_refno)) {
        $query = "SELECT ".FLDSYNCID." FROM ".TABSYNC." WHERE ".FLDSYNCKEY."
            = '$adr_refno' AND ".FLDSYNCENT." = 'address'";
        $daoSync = CRM_Core_DAO::executeQuery($query);
        $fldsyncid = FLDSYNCID;
        while ($daoSync->fetch()) {
            $address_id = $daoSync->$fldsyncid;
        }
    }
    /*
     * if $address_id is still empty, error
     */
    if (empty($address_id)) {
        return civicrm_create_error("Adres niet gevonden");
    }
    /*
     * check if address exists in CiviCRM
     */
    $checkparms = array("address_id" => $address_id);
    $res_check = civicrm_api3_dgwcontact_addressget($checkparms);
    if (civicrm_error($res_check)) {
        return civicrm_create_error("Adres niet gevonden");
    }
    /*
     * if location_type is invalid, error
     */
    if (isset($inparms['location_type'])) {
        $location_type_id = (int) getLocationTypeId($inparms['location_type']);
        if ($location_type_id == "") {
            return civicrm_create_error("Location_type is ongeldig");
        } else {
            $location_type = strtolower(trim($inparms['location_type']));
        }
    } else {
        $location_type = "";
    }
    /*
     * if is_primary is not 0 or 1, error
     */
    if (isset($inparms['is_primary'])) {
        $is_primary = $inparms['is_primary'];
        if ($is_primary != 0 && $is_primary != 1) {
            return civicrm_create_error("Is_primary is ongeldig");
        }
    } else {
        $is_primary = 0;
    }
    /*
     * if start_date > today and location_type is not toekomst, error
     */
    if (isset($start_date) && !empty($start_date)) {
        $start_date = date("Ymd", strtotime($start_date));
        if ($start_date > date("Ymd") && $location_type != "toekomst") {
            return civicrm_create_error("Combinatie location_type en
                start/end_date ongeldig");
        }
        /*
         * if location_type = toekomst and start_date is not > today, error
         */
        if ($location_type == "toekomst" && $start_date <= date("Ymd")) {
            return civicrm_create_error("Combinatie location_type en start/end_date
                ongeldig");
        }
    }
    /*
     * if end_date < today and location_type is not oud, error
     */
    if (isset($end_date) && !empty($end_date)) {
        $end_date = date("Ymd", strtotime($end_date));
        if ($end_date < date("Ymd") && $location_type != "oud") {
            return civicrm_create_error("Combinatie location_type en
                start/end_date ongeldig");
        }
        /*
         * if location_type = oud and end_date is empty or > today, error
         */
        if ($location_type == "oud") {
            if (empty($end_date) || $end_date > date("Ymd")) {
                return civicrm_create_error("Combinatie location_type en start/
                    end_date ongeldig");
            }
        }
    }
    /*
     * if postal_code passed an format invalid, error
     */
    if (isset($inparms['postal_code']) && !empty($inparms['postal_code'])) {
        $valid_postal = checkPostcodeFormat(trim($inparms['postal_code']));
        if (!$valid_postal) {
            return civicrm_create_error("Postcode is ongeldig");
        } else {
            $postal_code = trim($inparms['postal_code']);
        }
    }
    /*
     * if country_iso passed, check if country iso code exists in CiviCRM
     */
     if (isset($inparms['country_iso']) && !empty($inparms['country_iso'])) {
         $country_iso = trim($inparms['country_iso']);
         $query = "SELECT count(*) AS aantal, id FROM civicrm_country WHERE
             iso_code = '$country_iso'";
         $daoCountry = CRM_Core_DAO::executeQuery($query);
         while ($daoCountry->fetch()) {
             $aantal = $daoCountry->aantal;
         }
         if ($aantal == 0) {
             return civicrm_create_error("Country_iso $country_iso komt niet voor");
         } else {
             $country_id = $daoCountry->id;
         }
     }
     
     /*
      * if street_number passed and not numeric, error
      */
     if (isset($inparms['street_number'])) {
         $street_number = trim($inparms['street_number']);
         if (empty($street_number)) {
             $street_number = null;
         } else {
             if (!is_numeric($street_number)) {
                 return civicrm_create_error( "Huisnummer is niet numeriek");
             }
         }
     }

    /*
     * all validation passed, first retrieve address to get all current values
     * for total update record
     */
    $qry1 = "SELECT * FROM civicrm_address WHERE id = $address_id";
    $daoAddressCurrent = CRM_Core_DAO::executeQuery($qry1);
    $daoAddressCurrent->fetch();
    $contactID = $daoAddressCurrent->contact_id;
    if (!isset($location_type_id)) {
        $location_type_id = $daoAddressCurrent->location_type_id;
    }
    if (!isset($inparms['street_name'])) {
        $street_name = $daoAddressCurrent->street_name;
    } else {
        $street_name = trim($inparms['street_name']);
    }
    if (!isset($inparms['street_number'])) {
        $street_number = $daoAddressCurrent->street_number;
    } else {
        $street_number = $inparms['street_number'];
    }
    if (!isset($inparms['street_suffix'])) {
        $street_suffix = $daoAddressCurrent->street_number_suffix;
    } else {
        $street_suffix = trim($street_suffix);
    }
    if (!isset($inparms['city'])) {
        $city = $daoAddressCurrent->city;
    } else {
        $city = trim($inparms['city']);
    }
    if (!isset($inparms['postal_code'])) {
        $postal_code = $daoAddressCurrent->postal_code;
    }
    if (!isset($country_id)) {
        $country_id = $daoAddressCurrent->country_id;
    }
    /*
     * if is_primary = 1, set all existing records for contact to is_primary 0
     */
    if ($is_primary == 1) {
        $qry2 = "UPDATE civicrm_address SET is_primary = 0 WHERE contact_id =
            $contactID";
        $daoPrimUpd = CRM_Core_DAO::executeQuery($qry2);
    } else {
        $is_primary = 0;
    }
    /*
     * compute street address
     */
    $street_address = $street_name;
    if (!empty($street_number)) {
        $street_address = $street_address." ".$street_number;
    }
    if (!empty($street_suffix)) {
        $street_address = $street_address." ".$street_suffix;
    }
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
     * issue 132: set supplemental address for end date if
     * not location type oud
     */
    if ($location_type != "oud" && isset($end_date) && 
		!empty($end_date)) {
		$datum = date("d-m-Y", strtotime($end_date));
		$sup = "(Tot $datum)";
	}
    /*
     * issue 139, Erik Hommel, 30 nov 2010
     * If current location_type_id = 1 and end_date is passed
     * as parm and not in future, make address "Oud"
     */
    if (isset($end_date) && !empty($end_date)) {
        if ($daoAddressCurrent->location_type_id == 1) {
            if ($end_date <= date("Ymd")) {
                $location_type_id = LOCOUD;
                /*
                 * delete current address OUD from table
                 */
                $qry4 = "DELETE FROM civicrm_address WHERE contact_id =
                    $contactID AND location_type_id = $location_type_id";
                CRM_Core_DAO::executeQuery($qry4);
            }
        }
    }
    /*
     * update address with new values
     */
    if (empty($street_number)) {
        $qry3 = "UPDATE civicrm_address SET location_type_id =
        $location_type_id, is_primary = $is_primary, street_address =
        '$street_address', street_name = '$street_name',
        street_number_suffix = '$street_suffix', city = '$city',
        postal_code = '$postal_code', country_id = $country_id";
    } else {
        $qry3 = "UPDATE civicrm_address SET location_type_id =
            $location_type_id, is_primary = $is_primary, street_address =
            '$street_address', street_name = '$street_name', street_number =
            $street_number, street_number_suffix = '$street_suffix', city =
            '$city', postal_code = '$postal_code', country_id = $country_id";
    }
    if (isset($sup) && !empty($sup)) {
        $qry3 = $qry3.", supplemental_address_1 = '$sup'";
    }
    $qry3 = $qry3." WHERE id = $address_id";
    CRM_Core_DAO::executeQuery($qry3);
    /*
     * issue 158: if the address belongs to a hoofdhuurder, update the household
     * address too
     */
    $qry4 = "SELECT contact_id FROM civicrm_address WHERE id = $address_id";
    $res4 = CRM_Core_DAO::executeQuery($qry4);
    if ($res4->fetch()) {
        if (isset($res4->contact_id)) {
            $huishoudenID = is_hoofdhuurder($res4->contact_id);
            if ($huishoudenID != 0) {
                /*
                 * update huishouden address if there is one, if not create
                 */
                $qry5 = "SELECT count(*) AS aantal FROM civicrm_address WHERE
                    contact_id = $huishoudenID";
                $res5 = CRM_Core_DAO::executeQuery($qry5);
                if ($res5->fetch()) {
                    if ($res5->aantal == 0) {
                        $qry6 = "INSERT INTO civicrm_address SET contact_id =
                            $huishoudenID, location_type_id = $location_type_id";
                    } else {
                        $qry6 = "UPDATE civicrm_address SET location_type_id =
                            $location_type_id";
                    }
                }
                if (empty($street_number)) {
                    $qry6 .= ", is_primary = $is_primary, street_address =
                        '$street_address', street_name = '$street_name',
                        street_number_suffix = '$street_suffix', city = '$city',
                        postal_code = '$postal_code', country_id = $country_id";
                    } else {
                        $qry6 .= ", is_primary = $is_primary, street_address =
                            '$street_address', street_name = '$street_name',
                            street_number = $street_number,
                            street_number_suffix = '$street_suffix', city =
                            '$city', postal_code = '$postal_code', country_id =
                            $country_id";
                    }
                    if (isset($sup) && !empty($sup)) {
                        $qry6 = $qry6.", supplemental_address_1 = '$sup'";
                    }
                    if (substr($qry6, 0, 6) == "UPDATE") {
                        $qry6 = $qry6." WHERE contact_id = $huishoudenID";
                    }
                    CRM_Core_DAO::executeQuery($qry6);
            }
        }
    }
    /*
     * set new adr_refno in synctable if passed
     */
    if (isset($inparms['adr_refno']) && !empty($inparms['adr_refno'])) {
        $refno = trim($inparms['adr_refno']);
        $upd_address = "UPDATE ".TABSYNC." SET ".FLDSYNCKEY." = $refno WHERE "
            .FLDSYNCID." = $address_id";
        CRM_Core_DAO::executeQuery($upd_address);
    }
	/*
	 * issue 239: if there is only one address left, make this primary
	 */
	$checkqry = "SELECT COUNT(id) AS aantal, is_primary FROM civicrm_address
		WHERE contact_id = $contactID";
	$daoCheckAantal = CRM_Core_DAO::executeQuery($checkqry);
	if ($daoCheckAantal->fetch()) {
		if ($daoCheckAantal->aantal == 1 &&
				$daoCheckAantal->is_primary == 0) {
			$updPrimary = "UPDATE civicrm_address SET is_primary = 1 WHERE
					contact_id = $contactID";
			CRM_Core_DAO::executeQuery($updPrimary);
		}
	}
	if (!empty($huishoudenID)) {
		$checkqry = "SELECT COUNT(id) AS aantal, is_primary FROM civicrm_address
			WHERE contact_id = $huishoudenID";
		$daoCheckAantal = CRM_Core_DAO::executeQuery($checkqry);
		if ($daoCheckAantal->fetch()) {
			if ($daoCheckAantal->aantal == 1 &&
					$daoCheckAantal->is_primary == 0) {
				$updPrimary = "UPDATE civicrm_address SET is_primary = 1 WHERE
					contact_id = $huishoudenID";
				CRM_Core_DAO::executeQuery($updPrimary);
			}
		}
	}
    $outparms['is_error'] = "0";
    return $outparms;
}
/*
 * Function to delete an e-mailadres in CiviCRM
 */
function civicrm_api3_dgwcontact_emaildelete($inparms) {
    /*
     * if no email_id or cde_refno passed, error
     */
    if (!isset($inparms['email_id']) && !isset($inparms['cde_refno'])) {
        return civicrm_create_error("Email_id en cde_refno ontbreken beiden");
    }
    if (isset($inparms['email_id'])) {
        $email_id = trim($inparms['email_id']);
    } else {
        $email_id = null;
    }
    if (isset($inparms['cde_refno'])) {
        $cde_refno = trim($inparms['cde_refno']);
    } else {
        $cde_refno = null;
    }
    if (empty($email_id) && empty($cde_refno)) {
        return civicrm_create_error("Email_id en cde_refno ontbreken beiden");
    }
    /*
     * if $cde_refno is used, retrieve $email_id from synchronisation First table
     */
    if (!empty($cde_refno)) {
        $query = "SELECT ".FLDSYNCID." FROM ".TABSYNC." WHERE ".FLDSYNCKEY."
            = '$cde_refno' AND ".FLDSYNCENT." = 'email'";
        $daoSync = CRM_Core_DAO::executeQuery($query);
        $fldsyncid = FLDSYNCID;
        while ($daoSYnc->fetch()) {
            $email_id = $daoSync->$fldsyncid;
        }
    }
    /*
     * if $email_id is still empty, error
     */
    if (empty($email_id)) {
        return civicrm_create_error("Email niet gevonden");
    }
    /*
     * check if email exists in CiviCRM
     */
    $checkparms = array("email_id" => $email_id);
    $res_check = civicrm_api3_dgwcontact_emailget($checkparms);
    if (civicrm_error($res_check)) {
        return civicrm_create_error("Email niet gevonden");
    }
    /*
     * all validation passed, delete email from table
     */
    $qry = "DELETE FROM civicrm_email WHERE id = $email_id";
    $daoEmailDel = CRM_Core_DAO::executeQuery($qry);
    $outparms['is_error'] = "0";
    return $outparms;
}
/*
 * Function to delete an address in CiviCRM
 */
function civicrm_api3_dgwcontact_addressdelete($inparms) {
    /*
     * if no address_id or adr_refno passed, error
     */
    if (!isset($inparms['address_id']) && !isset($inparms['adr_refno'])) {
        return civicrm_create_error("Address_id en adr_refno ontbreken beiden");
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
        return civicrm_create_error("Address_id en adr_refno ontbreken beiden");
    }
    /*
     * if $adr_refno is used, retrieve $address_id from synchronisation First table
     */
    if (!empty($adr_refno)) {
        $query = "SELECT ".FLDSYNCID." FROM ".TABSYNC." WHERE ".FLDSYNCKEY."
            = '$adr_refno' AND ".FLDSYNCENT." = 'address'";
        $daoSync = CRM_Core_DAO::executeQuery($query);
        $fldsyncid = FLDSYNCID;
        while ($daoSYnc->fetch()) {
            $address_id = $daoSync->$fldsyncid;
        }
    }
    /*
     * if $address_id is still empty, error
     */
    if (empty($address_id)) {
        return civicrm_create_error("Address niet gevonden");
    }
    /*
     * check if address exists in CiviCRM
     */
    $checkparms = array("address_id" => $address_id);
    $res_check = civicrm_api3_dgwcontact_addressget($checkparms);
    if (civicrm_error($res_check)) {
        return civicrm_create_error("Address niet gevonden");
    }
    /*
     * all validation passed, delete address from table
     */
    $qry = "DELETE FROM civicrm_address WHERE id = $address_id";
    $daoAddressDel = CRM_Core_DAO::executeQuery($qry);
    $outparms['is_error'] = "0";
    return $outparms;
}
/*
 * Function to remove contact from a CiviCRM group
 */
function civicrm_api3_dgwcontact_groupremove($inparms) {
    /*
     * if no contact_id or persoonsnummer_first passed, error
     */
    if (!isset($inparms['contact_id']) &&
            !isset($inparms['persoonsnummer_first'])) {
        return civicrm_create_error("Contact_id en persoonsnummer_first
            ontbreken beiden");
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
        return civicrm_create_error("Contact_id en persoonsnummer_first
            ontbreken beiden");
    }
    /*
     * if no group_id or group_name passed, error
     */
    if (!isset($inparms['group_id']) && !isset($inparms['group_name'])) {
        return civicrm_create_error("Group_id en group_name ontbreken beiden");
    }
    if (isset($inparms['group_id'])) {
        $group_id = trim($inparms['group_id']);
    } else {
        $group_id = null;
    }
    if (isset($inparms['group_name'])) {
        $group_name = trim($inparms['group_name']);
    } else {
        $group_name = null;
    }
    if (empty($group_id) && empty($group_name)) {
        return civicrm_create_error("Group_id en group_name ontbreken beiden");
    }
    /*
     * contact has to exist in CiviCRM, either with contact_id or with
     * persoonsnummer_first
     */
    if (isset($pers_nr) && !empty($pers_nr)) {
        $qry = "SELECT count(id) as aantal, entity_id FROM ".
            TABFIRSTPERS." WHERE ".FLDPERSNR." = '$pers_nr'";
        $daoPers = CRM_Core_DAO::executeQuery($qry);
        while ($daoPers->fetch()) {
            $aantal = $daoPers->aantal;
            $contact_id = $daoPers->entity_id;
        }
        if ($aantal == 0) {
            return civicrm_create_error("Contact niet gevonden");
        }
    }
    /*
     * Retrieve group_id for group if name used
     */
    if (isset($group_name) && !empty($group_name)) {
        $groupparms = array("title" => $group_name);
    } else {
        $groupparms = array("group_id" => $group_id);
    }
    $civigroup = &civicrm_group_get($groupparms);
    /*
     * Error if group not found
     */
    if (civicrm_error($civires1)) {
        return civicrm_create_error("Groep niet gevonden");
    } else {
        foreach ($civigroup as $group) {
            $group_id = $group['id'];
        }
    }
    /*
     * remove contact_id from group_id with standard API
     */
    $civiparms = array(
        "contact_id"    =>  $contact_id,
        "group_id"      =>  $group_id);
    $civires = &civicrm_group_contact_remove($civiparms);
    if (civicrm_error($civires)) {
        return civicrm_create_error($civires2['error_message']);
    } else {
            $outparms['is_error'] = "0";
    }
return $outparms;
}
/*
 * Function to update huurovereenkomst
 */
function civicrm_api3_dgwcontact_hovupdate($inparms) {
    /*
     * if no hov_nummer passed, error
     */
    if (!isset($inparms['hov_nummer'])) {
        return civicrm_create_error("Hov_nummer ontbreekt");
    } else {
        $hov_nummer = trim($inparms['hov_nummer']);
    }
    /*
     * if hov not found in CiviCRM, error (issue 240 check for
     * household table and organization table
     */
    $org_id = null;
    $huis_id = null;
    $type = null; 
    $huisqry = "SELECT entity_id, count(id) as aantal FROM ".TABHOV. " WHERE ".
		FLDHOVNR." = '$hov_nummer'";
	$daoHovHuis = CRM_Core_DAO::executeQuery($huisqry);	
	if ($daoHovHuis->fetch()) {
		$aantal = $daoHovHuis->aantal;
		$huis_id = $daoHovHuis->entity_id;
		$type = "huishouden";
		if ($aantal == 0) {
			$orgqry = "SELECT entity_id, count(id) as aantal FROM ".TABHOVORG.
				" WHERE ".FLDHOVNRORG." = '$hov_nummer'";
			$daoHovOrg = CRM_Core_DAO::executeQuery($orgqry);
			if ($daoHovOrg->fetch()) {
				$aantal = $daoHovOrg->aantal;
				$org_id = $daoHovOrg->entity_id;
				$type = "organisatie";
				if ($aantal == 0) {
					return civicrm_create_error("Huurovereenkomst niet gevonden");
				}
			}
		}
	}
    /*
     * if hh_persoon passed and not found in CiviCRM, error
     * issue 240: or if type = organization
     */
    if (isset($inparms['hh_persoon']) && !empty($inparms['hh_persoon'])) {
		if ($type == "organisatie") {
			return civicrm_create_error("Hoofdhuurder kan niet opgegeven 
				worden bij een huurovereenkomst van een organisatie");
		}
        $hh_persoon = trim($inparms['hh_persoon']);
        $hhparms = array("persoonsnummer_first" => $hh_persoon);
        $res_hh = civicrm_api3_dgwcontact_get($hhparms);
        if (civicrm_error($res_hh)) {
            return civicrm_create_error("Hoofdhuurder niet gevonden");
        } else {
            if ($res_hh[0]['record_count'] == 0) {
                return civicrm_create_error("Hoofdhuurder niet gevonden");
            } else {
				if ($res_hh[1]['contact_type'] == "organization") {
				} else {
                $hh_id = $res_hh[1]['contact_id'];
				}
            }
        }
    }
    /*
     * if mh_persoon passed and not found in CiviCRM, error (also check
     * new type for issue 240)
     */
    if (isset($inparms['mh_persoon']) && !empty($inparms['mh_persoon'])) {
 		if ($type == "organisatie") {
			return civicrm_create_error("Medehuurder kan niet opgegeven 
				worden bij een huurovereenkomst van een organisatie");
		}
        $mh_persoon = trim($inparms['mh_persoon']);
        $mhparms = array("persoonsnummer_first" => $mh_persoon);
        $res_mh = civicrm_api3_dgwcontact_get($mhparms);
        if (civicrm_error($res_mh)) {
            return civicrm_create_error("Medehuurder niet gevonden");
        } else {
            if ($res_mh[0]['record_count'] == 0) {
                return civicrm_create_error("Medehuurder niet gevonden");
            } else {
                $mh_id = $res_mh[1]['contact_id'];
            }
        }
    }
     
    /*
     * if start_date passed and format invalid, error
     */
    if (isset($inparms['start_date']) && !empty($inparms['start_date'])) {
        $valid_date = checkDateFormat($inparms['start_date']);
        if (!$valid_date) {
            return civicrm_create_error("Onjuiste formaat start_date");
        } else {
          $start_date = date("Ymd", strtotime($inparms['start_date']));
        }
    }
    /*
     * if end_date passed and format invalid, error
     */
    if (isset($inparms['end_date']) && !empty($inparms['end_date'])) {
        $valid_date = checkDateFormat($inparms['end_date']);
        if (!$valid_date) {
            return civicrm_create_error("Onjuiste formaat end_date");
        } else {
          $end_date = date("Ymd", strtotime($inparms['end_date']));
        }
    }
    /*
     * if hh_start_date passed and format invalid, error
     */
    if (isset($inparms['hh_start_date']) && !empty($inparms['hh_start_date'])) {
        $valid_date = checkDateFormat($inparms['hh_start_date']);
        if (!$valid_date) {
            return civicrm_create_error("Onjuiste formaat hh_start_date");
        } else {
          $hh_start_date = date("Ymd", strtotime($inparms['hh_start_date']));
        }
    }
    /*
     * if hh_end_date passed and format invalid, error
     */
    if (isset($inparms['hh_end_date']) && !empty($inparms['hh_end_date'])) {
        $valid_date = checkDateFormat($inparms['hh_end_date']);
        if (!$valid_date) {
            return civicrm_create_error("Onjuiste formaat hh_end_date");
        } else {
          $hh_end_date = date("Ymd", strtotime($inparms['hh_end_date']));
        }
    }
    /*
     * if mh_start_date passed and format invalid, error
     */
    if (isset($inparms['mh_start_date']) && !empty($inparms['mh_start_date'])) {
        $valid_date = checkDateFormat($inparms['mh_start_date']);
        if (!$valid_date) {
            return civicrm_create_error("Onjuiste formaat mh_start_date");
        } else {
          $mh_start_date = date("Ymd", strtotime($inparms['mh_start_date']));
        }
    }
    /*
     * if mh_end_date passed and format invalid, error
     */
    if (isset($inparms['mh_end_date']) && !empty($inparms['mh_end_date'])) {
        $valid_date = checkDateFormat($inparms['mh_end_date']);
        if (!$valid_date) {
            return civicrm_create_error("Onjuiste formaat mh_end_date");
        } else {
          $mh_end_date = date("Ymd", strtotime($inparms['mh_end_date']));
        }
    }
    /*
     * Validation passed, process depending on type (issue 240)
     */
    if ($type == "organisatie") {
		/*
		 * organization: update fields if passed in parms (issue 240)
		 */
		$orgQry = "SELECT * FROM ".TABHOVORG." WHERE ".FLDHOVNRORG.
			" = '$hov_nummer'";
		$daoHovOrg = CRM_Core_DAO::executeQuery($orgQry);
		if ($daoHovOrg->fetch()) {
			$fldhovvgeorg = FLDHOVVGEORG;
			$fldhovadresorg = FLDHOVADRESORG;
			$fldhovbegorg = FLDHOVBEGORG;
			$fldhovendorg = FLDHOVENDORG;
			$fldhovcororg = FLDHOVCORORG;
			if (isset($inparms['vge_nummer'])) {
				$vge_nummer = $inparms['vge_nummer'];
			} else {
				$vge_nummer = $daoHovOrg->$fldhovvgeorg;
			}
			if (isset($inparms['vge_adres'])) {
				$vge_adres = $inparms['vge_adres'];
			} else {
				$vge_adres = $daoHovOrg->$fldhovadresorg;
			}
			if (isset($inparms['corr_name'])) {
				$corr_name = $inparms['corr_name'];
			} else {
				$corr_name = $daoHovOrg->$fldhovcororg;
			}
			$orgUpd = "UPDATE ".TABHOVORG." SET ".FLDHOVVGEORG." = 
				'$vge_nummer', ".FLDHOVADRESORG." = '$vge_adres', ".
				FLDHOVCORORG." = '$corr_name'"; 
			if (!isset($end_date)) {
				$end_date = $daoHovOrg->$fldhovendorg;
			}
			if (!isset($start_date)) {
				$start_date = $daoHovOrg->$fldhovbegorg;
			}
			if (!empty($start_date)) {
				$orgUpd .= ", ". FLDHOVBEGORG." = '$start_date'";
			}
			if (!empty($end_date)) {
				$orgUpd .= ", ". FLDHOVENDORG." = '$end_date'";
			}
			$orgUpd .= " WHERE ".FLDHOVNRORG." = '$hov_nummer'";
			CRM_Core_DAO::executeQuery($orgUpd);
		}
	} else {
		/*
		 * individual/household
		 */
		$huisQry = "SELECT * FROM ".TABHOV." WHERE ".FLDHOVNR.
			" = '$hov_nummer'";
		$daoHovHuis = CRM_Core_DAO::executeQuery($huisQry);
		if ($daoHovHuis->fetch()) {
			$fldhovnr = FLDHOVNR;
			$fldhovbeg = FLDHOVBEG;
			$fldhovend = FLDHOVEND;
			$fldhovvge = FLDHOVVGE;
			$fldhovadres = FLDHOVADRES;
			$fldhovcor = FLDHOVCOR;
		 
			if (isset($start_date)) {
				$hov_beg = $start_date;
			}
			if (isset($inparms['vge_nummer'])) {
				$hov_vge = trim($inparms['vge_nummer']);
			} else {
				$hov_vge = $daoHovHuis->$fldhovvge;
			}
			if (isset($inparms['vge_adres'])) {
				$hov_adres = trim($inparms['vge_adres']);
			} else {
				$hov_adres = $daoHovHuis->$fldhovadres;
			}
			if (isset($inparms['corr_name'])) {
				$hov_cor = trim($inparms['corr_name']);
				/*
				 * update name household (issue 133, Erik Hommel, 30 nov 2010)
				 */
				$huishoudparms = array(
					"contact_id"        =>  $huis_id,
					"contact_type"      =>  "Household",
					"name"              =>  $hov_cor);
				civicrm_api3_dgwcontact_update($huishoudparms);
			} else {
				$hhov_cor = $daoHovHuis->$fldhovcor;
			}
			$huisUpd = "UPDATE ".TABHOV." SET ".FLDHOVVGE." = '$hov_vge', ".
				FLDHOVADRES." = '$hov_adres', ".FLDHOVCOR." = '$hov_cor'";
			if (!isset($end_date)) {
				$end_date = $daoHovHuis->$fldhovend;
			}
			if (!isset($start_date)) {
				$start_date = $daoHovHuis->$fldhovbeg;
			}
			if (!empty($start_date)) {
				$huisUpd .= ", ". FLDHOVBEG." = '$start_date'";
			}
			if (!empty($end_date)) {
				$huisUpd .= ", ". FLDHOVEND." = '$end_date'";
			}
			$huisUpd .= " WHERE ".FLDHOVNR." = '$hov_nummer'";
			CRM_Core_DAO::executeQuery($huisUpd);
			/*
			 * if hh_persoon passed, check if relation hoofdhuurder or medehuurder
			 * already exists between persoon and huishouden.
			 */
			if (isset($hh_persoon)) {
				$qryrel = "SELECT count(*) AS aantal, id, relationship_type_id,
					start_date, end_date, is_active FROM civicrm_relationship WHERE
					relationship_type_id = ".RELHFD." AND contact_id_a = $hh_id";
				$daoRelhh = CRM_Core_DAO::executeQuery($qryrel);
				while ($daoRelhh->fetch()) {
					$rel_id = $daoRelhh->id;
					/*
					 * relationship type id = hoofdhuurder
					 */
					$rel_type_id = RELHFD;
					/*
					 * if start_date changed, set
					 */
					if (isset($hh_start_date) && !empty($hh_start_date)) {
						$rel_start_date = $hh_start_date;
					} else {
						unset($rel_start_date);
					}
					/*
					 * if end_date changed, set
					 */
					if (isset($hh_end_date) && !empty($hh_end_date)) {
						$rel_end_date = $hh_end_date;
					} else {
						unset($rel_end_date);
					}
					/*
					 * if end_date before or on today, is_active = 0
					 */
					$relupd = "UPDATE civicrm_relationship SET relationship_type_id =
						$rel_type_id";
					if (isset($rel_start_date)) {
						$relupd = $relupd.", start_date = '$rel_start_date'";
					}
					if (isset($rel_end_date)) {
						$relupd = $relupd.", end_date = '$rel_end_date'";
					}
					if (isset($rel_end_date) && $rel_end_date <= $date) {
						$rel_is_active = 0;
					} else {
						$rel_is_active = $daoRelhh->is_active;
					}
					$relupd = $relupd.", is_active = $rel_is_active where id = $rel_id";
					$daoUpdHH = CRM_Core_DAO::executeQuery($relupd);
				}
			}
			/*
			 * if mh_persoon passed, check if relation hoofdhuurder or medehuurder
			 * already exists between persoon and huishouden.
			 */
			if (isset($mh_persoon)) {
				$qryrel = "SELECT count(*) AS aantal, id, relationship_type_id,
					start_date, end_date, is_active FROM civicrm_relationship WHERE
					relationship_type_id = ".RELMEDE." AND contact_id_a = $mh_id";
				$daoRelmh = CRM_Core_DAO::executeQuery($qryrel);
				while ($daoRelmh->fetch()) {
					$rel_id = $daoRelmh->id;
					/*
					 * relationship type id = medehuurder
					 */
					$rel_type_id = RELMEDE;
					/*
					 * if start_date changed, set
					 */
					if (isset($mh_start_date)) {
						$rel_start_date = $mh_start_date;
					} else {
						$rel_start_date = $daoRelmh->start_date;
					}
					/*
					 * if end_date changed, set
					 */
					if (isset($mh_end_date) && !empty($mh_end_date)) {
						$rel_end_date = $mh_end_date;
					} else {
						unset($rel_end_date);
					}
					/*
					 * if end_date before or on today, is_active = 0
					 */
					if (isset($rel_end_date)) {
						if ($rel_end_date <= $date) {
							$rel_is_active = 0;
						} else {
							$rel_is_active = $daoRelmh->is_active;
						}
						$relupd = "UPDATE civicrm_relationship SET relationship_type_id
							= $rel_type_id, start_date = '$rel_start_date', end_date =
						'$rel_end_date', is_active = $rel_is_active where id = $rel_id";

					} else {
						$rel_is_active = $daoRelmh->is_active;
						$relupd = "UPDATE civicrm_relationship SET relationship_type_id
							= $rel_type_id, start_date = '$rel_start_date', is_active =
							$rel_is_active where id = $rel_id";

					}
					$daoUpdMH = CRM_Core_DAO::executeQuery($relupd);
				}
			}
			
		}
	}
    $outparms['is_error'] = "0";
    return $outparms;
}
/*
 * function to copy all addresses, phones and emails of a hoofdhuurder
 * to the huishouden
 */
function _civicrm_copy_hoofdhuurder($huishoudenID, $hoofdhuurderID) {
	/*
	 * process only if both huishouden and hoofdhuurder are not empty
	 */
	if (empty($huishoudenID) || empty($hoofdhuurderID)) {
		 return false;
	}
	/*
	 * select all addresses from hoofdhuurder and copy those to
	 * huishouden
	 */
	require_once('CRM/Core/DAO/Address.php');
	$addrDAO = new CRM_Core_DAO_Address();
	$fields = $addrDAO->fields();
	$adrSelQry = "SELECT * FROM civicrm_address WHERE contact_id = $hoofdhuurderID";
	$adrHoofd = CRM_Core_DAO::executeQuery($adrSelQry);
	while ($adrHoofd->fetch()) {
		 $adrInsQry = "INSERT INTO civicrm_address SET ";
		 foreach ($fields as $field) {
			if ($field['name'] != 'id' && $field['name'] != 'contact_id' 
				&& !empty($adrHoofd->$field['name'])) {
					if ($field['type'] == 2) {
						$veld = CRM_Core_DAO::escapeString( $adrHoofd->$field['name'] );
						$adrInsQry .= $field['name']. " = '".
							$veld."', ";
					} else {
						$adrInsQry .= $field['name']. " = ".
							$adrHoofd->$field['name'].", ";
					}
				}
		 }
		 $adrInsQry .= "contact_id = $huishoudenID";
		 CRM_Core_DAO::executeQuery($adrInsQry);
	}
	 /*
	  * select all phones from hoofdhuurder and copy those to
	  * huishouden
	  */
	require_once('CRM/Core/DAO/Phone.php');
	$phoneDAO = new CRM_Core_DAO_Phone();
	$fields = $phoneDAO->fields();
	$phoneSelQry = "SELECT * FROM civicrm_phone WHERE contact_id = $hoofdhuurderID";
	$phoneHoofd = CRM_Core_DAO::executeQuery($phoneSelQry);
	while ($phoneHoofd->fetch()) {
		 $phoneInsQry = "INSERT INTO civicrm_phone SET ";
		 foreach ($fields as $field) {
			if ($field['name'] != 'id' && $field['name'] != 'contact_id' 
				&& !empty($phoneHoofd->$field['name'])) {
					if ($field['type'] == 2) {
						$veld = CRM_Core_DAO::escapeString( $phoneHoofd->$field['name'] );
						$phoneInsQry .= $field['name']. " = '".
							$veld."', ";
					} else {
						$phoneInsQry .= $field['name']. " = ".
							$phoneHoofd->$field['name'].", ";
					}
				}
		 }
		 $phoneInsQry .= "contact_id = $huishoudenID";
		 CRM_Core_DAO::executeQuery($phoneInsQry);
	}
	 /*
	  * select all emails from hoofdhuurder and copy those to
	  * huishouden
	  */
	require_once('CRM/Core/DAO/Email.php');
	$emailDAO = new CRM_Core_DAO_Email();
	$fields = $emailDAO->fields();
	$emailSelQry = "SELECT * FROM civicrm_email WHERE contact_id = $hoofdhuurderID";
	$emailHoofd = CRM_Core_DAO::executeQuery($emailSelQry);
	while ($emailHoofd->fetch()) {
		 $emailInsQry = "INSERT INTO civicrm_email SET ";
		 foreach ($fields as $field) {
			if ($field['name'] != 'id' && $field['name'] != 'contact_id' 
				&& !empty($emailHoofd->$field['name'])) {
					if ($field['type'] == 2) {
						$veld = CRM_Core_DAO::escapeString( $emailHoofd->$field['name'] );
						$emailInsQry .= $field['name']. " = '".
							$veld."', ";
					} else {
						$emailInsQry .= $field['name']. " = ".
							$emailHoofd->$field['name'].", ";
					}
				}
		 }
		 $emailInsQry .= "contact_id = $huishoudenID";
		 CRM_Core_DAO::executeQuery($emailInsQry);
	}
}
/*
 * function to retrieve custom data for the contact type
 */
function _retrieveCustomData ( &$outparms ) {
	$output = array();
	$contactType = ucfirst( $outparms['contact_type'] );
	if ( !empty ($contactType) ) { 
		/*
		 * ophalen alle custom tabellen voor contact type of Contact
		 */
		$selCustomTables = 
		"SELECT * FROM civicrm_custom_group WHERE (extends = '$contactType' OR extends = 'Contact')";
		$daoCustomTables = CRM_Core_DAO::executeQuery( $selCustomTables );

		while ( $daoCustomTables->fetch() ) {
			if ( isset( $daoCustomTables->table_name ) ) {
				$tables[] = $daoCustomTables->table_name;
			/*
			 * retrieve data from table
			 */
				$selCustomData =
			"SELECT * FROM ".$daoCustomTables->table_name." WHERE entity_id = {$outparms['contact_id']}";
				$daoCustomData = CRM_Core_DAO::executeQuery( $selCustomData );
				if ( $daoCustomData->fetch() ) {
					/*
					 * retrieve all field names from found tables
					 */
					$selCustomFields = 
				"SELECT * FROM civicrm_custom_field WHERE custom_group_id = ".$daoCustomTables->id;
					$daoCustomFields = CRM_Core_DAO::executeQuery( $selCustomFields );
					
					$key = 0;
					while ( $daoCustomFields->fetch() ) {
						/*
						 * retrieve value from daoCustomData and set name and value for output
						 */
						if ( isset( $daoCustomFields->column_name ) ) {
							$output[$daoCustomTables->table_name][$key]['name'] = strtolower( $daoCustomFields->name );
							$output[$daoCustomTables->table_name][$key]['value'] = $daoCustomData->{$daoCustomFields->column_name};
							$key++;
						}
					}
				}
			}
		}
	}
	foreach ($output as $tabel=>$velden) {
		foreach ($velden as $veld) {
			$outparms[$veld['name']] = $veld['value'];
		}
	}
} 	
