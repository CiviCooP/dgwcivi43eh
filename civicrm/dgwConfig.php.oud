<?php
/*
+--------------------------------------------------------------------+
| Added PHP script IN CiviCRM dgwConfig.php                          |
+--------------------------------------------------------------------+
| Project       :       Implementation at De Goede Woning            |
| Author        :       Erik Hommel (EE-atWork, hommel@ee-atwork.nl  |
|                                    http://www.ee-atwork.nl)        |
| Date          :       11 Jan 2011                                  |
| Description   :       Configuration De Goede Woning, used in       |
|                       hooks and API's. Contains groups, names of   |
|                       custom fields, relationship types in         |
|                       constants                                    |
+--------------------------------------------------------------------+
 * retrieve DB user from CiviCRM config for logging REST requests
 */

if (!isset($config)) {
    require_once($_SERVER['DOCUMENT_ROOT'].
	'/sites/default/civicrm.settings.php');
    require_once 'CRM/Core/Config.php';
    $config =& CRM_Core_Config::singleton( );
}
$dsnarray = explode(":", $config->dsn);
if (isset($dsnarray[1])) {
    define("DGWLOGUSER", substr($dsnarray[1],2,strlen($dsnarray[1])-2));
}
if (isset($dsnarray[2])) {
    $dsnwwarray = explode("@", $dsnarray[2]);
    if (isset($dsnwwarray[0])) {
        define("DGWLOGWW",$dsnwwarray[0]);
    }
    if (isset($dsnwwarray[1])) {
        $dsnhostarray = explode("/", $dsnwwarray[1]);
        if (isset($dsnhostarray[0])) {
            define("DGWLOGHOST", $dsnhostarray[0]);
        }
        if (isset($dsnhostarray[1])) {
            $dsndbarray = explode("?", $dsnhostarray[1]);
            define("DGWCIVIDB", $dsndbarray[0]);
        }
    }
}
define("DGWLOGDB", "dgwsynclog");
define("CUSTOMDIR", $config->customPHPPathDir);

/*
 * constanten voor custom velden persoon
 */
define("CFPERSNR", "custom_1");
define("CFPERSBSN", "custom_2");
define("CFPERSBURG", "custom_3");
define("FLDPERSBURG", "burgerlijke_staat_3");
define("CFPERSTOT", "custom_4");
define("FLDPERSTOT", "totaal_debiteur_4");
define("TABFIRSTPERS", "civicrm_value_aanvullende_persoonsgegevens_1");
define("FLDPERSNR", "persoonsnummer_first_1");

 /*
  * constanten voor custom velden huurovereenkomst
  */
 define("TABHOV", "civicrm_value_huurovereenkomst_2");
 define("TABHOVORG", "civicrm_value_huurovereenkomst__org__11");
 define("CFHOVNR", "custom_5");
 define("CFHOVNRORG", "custom_58");
 define("FLDHOVNR", "hov_nummer_first_5");
 define("FLDHOVNRORG", "hov_nummer_58");
 define("CFHOVVGE", "custom_6");
 define("CFHOVVGEORG", "custom_59");
 define("FLDHOVVGE", "vge_nummer_first_6");
 define("FLDHOVVGEORG", "vge_nummer_59");
 define("CFHOVADRES", "custom_7");
 define("CFHOVADRESORG", "custom_60");
 define("FLDHOVADRES", "vge_adres_first_7");
 define("FLDHOVADRESORG", "vge_adres_60");
 define("CFHOVBEG", "custom_9");
 define("CFHOVBEGORG", "custom_61");
 define("FLDHOVBEG", "begindatum_hov_9");
 define("FLDHOVBEGORG", "begindatum_overeenkomst_61");
 define("FLDHOVEND", "einddatum_hov_10");
 define("FLDHOVENDORG", "einddatum_overeenkomst_62");
 define("CFHOVEND", "custom_10");
 define("CFHOVENDORG", "custom_62");
 define("CFHOVCOR", "custom_8");
 define("CFHOVCORORG", "custom_63");
 define("FLDHOVCOR", "correspondentienaam_first_8");
 define("FLDHOVCORORG", "naam_op_overeenkomst_63");
 /*
  * constanten voor custom velden koopovereenkomst
  */
 define("TABKOV", "civicrm_value_koopovereenkomst_3");
 define("CFKOVNR", "custom_11");
 define("CFKOVVGE", "custom_12");
 define("CFKOVADRES", "custom_13");
 define("CFKOVOV", "custom_14");
 define("CFKOVCOR", "custom_15");
 define("CFKOVDEF", "custom_16");
 define("CFKOVTYPE", "custom_17");
 define("CFKOVPRIJS", "custom_18");
 define("CFKOVNOTARIS", "custom_19");
 define("CFKOVTAXW", "custom_20");
 define("CFKOVTAX", "custom_21");
 define("CFKOVTAXD", "custom_22");
 define("CFKOVBOUW", "custom_23");
 define("CFKOVBOUWDAT", "custom_24");
 define("FLDKOVNR", "kov_nummer_first_11");
 define("FLDKOVVGE", "vge_nummer_kov_12");
 define("FLDKOVADRES", "vge_adres_kov_13");
 define("FLDKOVOVDAT", "datum_overdracht_14");
 define("FLDKOVCOR", "correspondentienaam_kov_15");
 define("FLDKOVDEF", "definitief_16");
 define("FLDKOVTYPE", "type_kov_17");
 define("FLDKOVPRIJS", "verkoopprijs_18");
 define("FLDKOVNOT", "notaris_19");
 define("FLDKOVTAXWA", "taxatiewaarde_20");
 define("FLDKOVTAX", "taxateur_21");
 define("FLDKOVTAXDAT", "taxatiedatum_22");
 define("FLDKOVBOUWDAT", "datum_bouwkundige_keuring_24");
 define("FLDKOVBOUW", "bouwkundige_23");

 /*
  * constanten voor custom velden interesse koop
  */
 define("CFWKNR", "custom_25");
 define("CFWKSIT", "custom_26");
 define("CFWKHOOFD", "custom_27");
 define("CFWKCORP", "custom_28");
 define("CFWKDAT", "custom_29");
 define("CFWKHOE", "custom_31");
 define("CFWKBRUTO", "custom_33");
 define("CFWKHUIS", "custom_30");
 define("CFWKPART", "custom_32");

 /*
  * constanten voor custom velden gegevens uit first organisaties
  */
 define("TABFIRSTORG", "civicrm_value_gegevens_uit_first_9");
 define("CFORGPERSNR", "custom_53");
 define("FLDORGPERSNR", "nr_in_first_53");

/*
 * default value for burg_staat
 */
define("DEFBURG", "7");
/*
 * constant for group Synchronize with First Noa
 */
define("FIRSTSYNC", "2");
/*
 * constanten voor tabel en velden synchronisatiegegevens First Sync
 */
define("TABSYNC", "civicrm_value_synchronisatie_first_noa_8");
define("FLDSYNCACT", "action_48");
define("CFSYNCACT", "custom_48");
define("FLDSYNCENT", "entity_49");
define("CFSYNCENT", "custom_49");
define("FLDSYNCID", "entity_id_50");
define("CFSYNCID", "custom_50");
define("FLDSYNCKEY", "key_first_51");
define("CFSYNCKEY", "custom_51");
define("CFSYNCDAT", "custom_52");
define("FLDSYNCDAT", "change_date_52");
define("TABSYNCERR", "civicrm_value_fouten_synchronisatie_first_6");
define("FLDERRDATE", "datum_synchronisatieprobleem_39");
define("FLDERRMSG", "foutboodschap_40");
define("FLDERRACT", "action_err_41");
define("FLDERRENT", "entity_err_42");
define("FLDERRID", "entity_id_err_43");
define("FLDERRKEY", "key_first_err_44");
/*
 * define constants for relationships
 */
define("RELHFD", "11");
define("RELMEDE", "13");
define("RELKOV", "12");
/*
 * define constant for location_type_id OUD, TOEKOMST en THUIS
 */
define("LOCOUD", "6");
define("LOCTOEKOMST", "7");
define("LOCTHUIS", "1");
/*
 * define constants for daily loading of KOV
 */
define("KOVFILENAME", "kov_");
define("KOVPATH", "/home/kov/");
define("KOVMAIL", "helpdesk@degoedewoning.nl");
define("KOVDB", "dgwcivi");
define("KOVTABLE", "kovimport");
define("KOVHEADER", "kovhdr");
define("KOVHOST", "localhost");
define("KOVFOUTGRP", "16");
define("KOVFOUTNM", "Koopovereenkomst Huishouden Fout");


/*
 * Function to retrieve labels of option values
 */
function getOptionValue($grouplabel = null, $groupname = null, $optionid = null,
        $optionname = null) {

    /*
     * error if group label and group_name are both empty
     */
    if (empty($grouplabel) && empty($groupname)) {
        return "error";
    }
    /*
     * error if optionid and optionname are both empty
     */
    if (empty($optionid) && empty($optionname)) {
        return "error";
    }

    require_once "CRM/Core/BAO/OptionGroup.php";
    require_once "CRM/Core/BAO/OptionValue.php";

    /*
     * retrieve the group id of the option value with the incoming
     * grouplabel or groupname
     */
    if (!empty($grouplabel)) {
        $civiparms = array("label" => $grouplabel);
    } else {
        $civiparms = array("name" => $groupname);
    }
    $defaults = array("");
    $optiongroup = CRM_Core_BAO_OptionGroup::retrieve($civiparms, $defaults);
    $groupid = $optiongroup->id;

    if (empty($groupid)) {
        return "error";
    }

    /*
     * retrieve the option label with the found groupid and incoming
     * value. Array $defaults is required as parm, but has no function here
     */

    if (isset($optionid) && !empty($optionid)) {
        $civiparms = array(
            "option_group_id"   =>  $groupid,
            "value"             =>  $optionid);
    } else {
        if (isset($optionname) && !empty($optionname)) {
        $civiparms = array(
            "option_group_id"   =>  $groupid,
            "label"             =>  $optionname);
        }
    }

    $optionlabel = CRM_Core_BAO_OptionValue::retrieve($civiparms, $defaults);
    if (empty($optionlabel)) {
        return "error";
    } else {
        if (isset($optionid)) {
            return $optionlabel->label;
        } else {
            return $optionlabel->value;
        }
    }
}
/*
 * Function to retrieve name of location_type with location_type_id
 */
function getLocationType($type_id) {

    /*
     * error if type_id is empty
     */
    if (empty($type_id)) {
        return civicrm_create_error("Empty location_type_id passed as parameter
            in getLocationType");
    } else {
        /*
         * error if location_type_id not numeric
         */
        if (!is_numeric($type_id)) {
            return civicrm_create_error("Non numeric location_type_id $type_id
                    passed as parameter in getLocationType");
        }
    }

    /*
     * build query to retrieve name from table
     */
    $query = "SELECT name FROM civicrm_location_type WHERE
        id = $type_id";
    $qryparms = array( 1 => array( 1, 'Integer' ) );
    $dao = & CRM_Core_DAO::executeQuery($query, $qryparms);
    if ( $dao->fetch( ) ) {
        return ($dao->name);
    } else {
        return "";
    }

}
/*
 * Function to retrieve id of location_type with location_type
 */
function getLocationTypeId($name) {

    /*
     * error if name is empty
     */
    if (empty($name)) {
        return civicrm_create_error("Empty location_type passed as parameter
            in getLocationTypeId");
    }

    /*
     * build query to retrieve id from table
     */
    $query = "SELECT id FROM civicrm_location_type WHERE
        name = '$name'";
    $qryparms = array( 1 => array( 1, 'Integer' ) );
    $dao = & CRM_Core_DAO::executeQuery($query, $qryparms);
    if ( $dao->fetch( ) ) {
        $id = $dao->id;
    } else {
        return "";
    }
    return $id;

}
/*
 * Function to check BSN with 11-check
 */
function validateBsn($bsn) {
    $bsn = trim(strip_tags($bsn));
    /*
     * if bsn is empty, return false
     */
    if (empty($bsn)) {
        return false;
    }
    /*
     * if bsn contains non-numeric digits, return false
     */
    if (!is_numeric($bsn)) {
        return false;
    }
    /*
     * if bsn has 8 digits, put '0' in front
     */
    if (strlen($bsn) == 8) {
        $bsn = "0".$bsn;
    }
    /*
     * if length bsn is not 9 now, return false
     */
    if (strlen($bsn) != 9) {
        return false;
    }

    $digits = array("");
    /*
     * put each digit in array
     */
    $i = 0;
    while ($i < 9) {
        $digits[$i] = substr($bsn,$i,1);
        $i++;
    }
    /*
     * compute total for 11 check
     */
    $check = 0;
    $number1 = $digits[0] * 9;
    $number2 = $digits[1] * 8;
    $number3 = $digits[2] * 7;
    $number4 = $digits[3] * 6;
    $number5 = $digits[4] * 5;
    $number6 = $digits[5] * 4;
    $number7 = $digits[6] * 3;
    $number8 = $digits[7] * 2;
    $number9 = $digits[8] * -1;
    $check = $number1 + $number2 + $number3 + $number4 + $number5 + $number6 +
        $number7 + $number8 + $number9;
    /*
     * divide check by 11 and use remainder
     */
    $remain = $check % 11;
    if ($remain == 0) {
        return true;
    } else {
        return false;
    }
}
/*
 * function to check the date format
 */
function checkDateFormat($indate) {
    /*
     * default false
     */
    $valid = false;
    /*
     * if length date = 8, not separated
     */
    if (strlen($indate) == 8) {
        $year = substr($indate,0,4);
        $month = substr($indate,4,2);
        $day = substr($indate, 6, 2);
    } else {
        /*
         * date parts separated by "-"
        */
        $dateparts = explode("-",$indate);
        if (isset($dateparts[2]) && !isset($dateparts[3])) {
            $month = $dateparts[1];
            if(strlen($dateparts[0]) == 2) {
                $day = (int) $dateparts[0];
                $year = (int) $dateparts[2];
            } else {
                $day = (int) $dateparts[2];
                $year = (int) $dateparts[0];
            }
        } else {
            /*
             * separated by "/"
             */
            $dateparts = explode("/", $indate);
            if (isset($dateparts[2]) && !isset($dateparts[3])) {
                $year = $dateparts[0];
                $month = $dateparts[1];
                $day = $dateparts[2];
            }
        }
    }
    if (isset($year) && isset($month) && isset($day)) {
        /*
         * only valid if all numeric
         */
        if (is_numeric($year) && is_numeric($month) && is_numeric($day)) {
            if ($month > 0 && $month < 13) {
                if ($year > 1800 && $year < 2500) {
                    if ($day > 0 && $day < 32) {
                        switch($month) {
                            case 1:
                                $valid = true;
                                break;
                            case 2:
                                if ($day < 30) {
                                    $valid = true;
                                }
                                break;
                            case 3:
                                $valid = true;
                                break;
                            case 4:
                                if ($day < 31) {
                                    $valid = true;
                                }
                                break;
                            case 5:
                                $valid = true;
                                break;
                            case 6:
                                if ($day < 31) {
                                    $valid = true;
                                }
                                break;
                            case 7:
                                $valid = true;
                                break;
                            case 8:
                                $valid = true;
                                break;
                            case 9:
                                if ($day < 31) {
                                    $valid = true;
                                }
                                break;
                            case 10:
                                $valid = true;
                                break;
                            case 11:
                                if ($day < 31) {
                                    $valid = true;
                                }
                                break;
                            case 12:
                                $valid = true;
                                break;
                        }

                    }
                }
            }
        }
    }
    return $valid;
}
/*
 * Function to check format of postcode (Dutch) 1234AA or 1234 AA
 */
function checkPostcodeFormat($postcode) {
    /*
     * if postcode empty, false
     */
    if (empty($postcode)) {
        return false;
    }
    /*
     * if length postcode not 6 or 7, error
     */
    if (strlen($postcode) != 6 && strlen($postcode) != 7) {
        return false;
    }
    /*
     * split in 2 parts depending on length
     */
    $num = substr($postcode,0,3);
    if (strlen($postcode == 6)) {
        $alpha = substr($postcode,4,2);
    } else {
        $alpha = substr($postcode,5,2);
    }
    /*
     * if $num is not numeric, error
     */
    if (!is_numeric(($num))) {
        return false;
    }
    /*
     * if $alpha not letters, error
     */
    if (!ctype_alpha($alpha)) {
        return false;
    }
    return true;
}
/*
 * function to check if a contact is a hoofdhuurder
 */
function is_hoofdhuurder($contact_id) {
    /*
     * only if contact_id is not empty
     */
    if (empty($contact_id)) {
        return 0;
    }
    /*
     * check if there is a relationship 'hoofdhuurder' for the contact_id
     */
    $rel_hfd_id = RELHFD;
    $sel_hoofd = "SELECT * FROM civicrm_relationship WHERE relationship_type_id
        = $rel_hfd_id AND contact_id_a = $contact_id";
    $relatie = CRM_Core_DAO::executeQuery($sel_hoofd);
    if ($relatie->fetch()) {
        return $relatie->contact_id_b;
    } else {
        return 0;
    }
}

?>
