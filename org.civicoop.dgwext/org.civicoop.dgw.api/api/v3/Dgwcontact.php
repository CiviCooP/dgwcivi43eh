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