<?php
/*
+--------------------------------------------------------------------+
| Added PHP script IN CiviCRM dgwLaadKovMnup                         |
+--------------------------------------------------------------------+
| Project       :   Implementation at De Goede Woning                |
| Author        :   Erik Hommel (EE-atWork, hommel@ee-atwork.nl      |
|                   http://www.ee-atwork.nl)                         |
| Date          :   30 maart 2011                                    |
| Description   :   Script zet koopovereenkomsten uit First over     |
|       	    naar CiviCRM. Het laden kan via een cronjob      |
|		    gebeuren of via een menuoptie. Dat wordt via     |
|                   een GET variabele doorgegeven (mode)             |
+--------------------------------------------------------------------+
*/
/**
 * Upgrade to CiviCRM 4.3.4 and remove mode param
 * @author Erik Hommel (erik.hommel@civicoop.org)
 *
 */
@date_default_timezone_set( 'Europe/Amsterdam' );
set_time_limit(0);
ini_set( 'display_errors', '1');
if ( !isset( $config ) ) {
    require_once($_SERVER['DOCUMENT_ROOT'].
	'/dgw43/sites/default/civicrm.settings.php');
    require_once 'CRM/Core/Config.php';
    $config = CRM_Core_Config::singleton( );
}
require_once 'CRM/Utils/Mail.php';
require_once 'CRM/Utils/DgwUtils.php';

$isError = false;
/*
 * check if extension org.civicoop.dgw.custom is enabled, if not this
 * program can not run
 */
$reqExtCustom = false;
$daoExtension = CRM_Core_DAO::executeQuery( "SELECT * FROM civicrm_extension");
while ( $daoExtension->fetch() ) {
    if ( isset( $daoExtension->full_name ) ) {
        if ( $daoExtension->full_name == "org.civicoop.dgw.custom" ) {
            if ( isset( $daoExtension->is_active ) ) {
                if ( $daoExtension->is_active == 1 ) {
                    $reqExtCustom = true;
                }
            }
        }
    }
}
if ( !$reqExtCustom ) {
    $isError = true;
    $errorText = "Het dagelijks laden van de koopovereenkomsten naar CiviCRM is mislukt omdat de CiviCRM extensie org.civicoop.dgw.custom niet aan staat.";
    $errorTitle = "Laden KOV naar CiviCRM is mislukt, extensie custom staat uit";
    sendError( $errorTitle, $errorText );
}
if ( !$isError ) {
    /*
     * check if file exists
     */
    $kovPath = CRM_Utils_DgwUtils::getDgwConfigValue( 'kov pad' );
    $kovFileName = CRM_Utils_DgwUtils::getDgwConfigValue( 'kov bestandsnaam' );
    $kovSource = $kovPath.$kovFileName.date("Ymd").".csv";
    if (!file_exists($kovSource)) {
        $isError = true;
        $errorText = "Het dagelijks laden van de koopovereenkomsten naar CiviCRM is mislukt omdat het bestand ".$kovSource." niet bestaat. Zorg ervoor dat het bestand alsnog op de juiste plek op de server komt en laad dan de koopovereenkomsten in CiviCRM middels de menuoptie Beheer/Laden koopovereenkomsten.";
        $errorTitle = "Laden KOV naar CiviCRM is mislukt, bestand bestaat
                niet";
        sendError( $errorTitle, $errorText );
    }
}
if ( !$isError ) {
    /*
     * truncate load and header table and load new data
     */
    $kovTable = CRM_Utils_DgwUtils::getDgwConfigValue( 'kov tabel' );
    CRM_Core_DAO::executeQuery( "TRUNCATE TABLE $kovTable" );
    $sourceData = fopen( $kovSource, 'r');
    while ( $sourceRow = fgetcsv( $sourceData, 0, ";" ) ) {
        $insImport = "INSERT INTO $kovTable SET ";
        $insFields = array( );
        if ( isset( $sourceRow[0] ) ) {
            $insFields[] = "kov_nr = {$sourceRow[0]}";
        }
        if ( isset( $sourceRow[1] ) ) {
            $insFields[] = "vge_nr = {$sourceRow[1]}";
        }
        if ( isset( $sourceRow[2] ) ) {
            $insFields[] = "pers_nr = {$sourceRow[2]}";
        }
        if ( isset( $sourceRow[3] ) ) {
            $sourceValue = CRM_Core_DAO::escapeString( $sourceRow[3] );
            $insFields[] = "corr_naam = '$sourceValue'";
        }
        if ( isset( $sourceRow[4] ) ) {
            $sourceValue = CRM_Core_DAO::escapeString( $sourceRow[4] );
            $insFields[] = "ov_datum = '$sourceValue'";
        }
        if ( isset( $sourceRow[5] ) ) {
            $sourceValue = CRM_Core_DAO::escapeString( $sourceRow[5] );
            $insFields[] = "vge_adres = '$sourceValue'";
        }
        if ( isset( $sourceRow[6] ) ) {
            $sourceValue = CRM_Core_DAO::escapeString( $sourceRow[6] );
            $insFields[] = "type = '$sourceValue'";
        }
        if ( isset( $sourceRow[8] ) ) {
            $sourceValue = CRM_Core_DAO::escapeString( $sourceRow[8] );
            $insFields[] = "prijs = '$sourceValue'";
        }
        if ( isset( $sourceRow[9] ) ) {
            $sourceValue = CRM_Core_DAO::escapeString( $sourceRow[9] );
            $insFields[] = "notaris = '$sourceValue'";
        }
        if ( isset( $sourceRow[10] ) ) {
            $sourceValue = CRM_Core_DAO::escapeString( $sourceRow[10] );
            $insFields[] = "tax_waarde = '$sourceValue'";
        }
        if ( isset( $sourceRow[11] ) ) {
            $sourceValue = CRM_Core_DAO::escapeString( $sourceRow[11] );
            $insFields[] = "taxateur = '$sourceValue'";
        }
        if ( isset( $sourceRow[12] ) ) {
            $sourceValue = CRM_Core_DAO::escapeString( $sourceRow[12] );
            $insFields[] = "tax_datum = '$sourceValue'";
        }
        if ( isset( $sourceRow[13] ) ) {
            $sourceValue = CRM_Core_DAO::escapeString( $sourceRow[13] );
            $insFields[] = "bouwkundige = '$sourceValue'";
        }
        if ( isset( $sourceRow[14] ) ) {
            $sourceValue = CRM_Core_DAO::escapeString( $sourceRow[14] );
            $insFields[] = "bouw_datum = '$sourceValue'";
        }
        if ( isset( $sourceRow[15] ) ) {
            $sourceValue = CRM_Core_DAO::escapeString( $sourceRow[15] );
            $insFields[] = "definitief = '$sourceValue'";
        }
        $insImport .= implode( ", ", $insFields );
        CRM_Core_DAO::executeQuery( $insImport );
    }
    $kovHeader = CRM_Utils_DgwUtils::getDgwConfigValue( 'kov header' );
    CRM_Core_DAO::executeQuery( "TRUNCATE TABLE $kovHeader" );
    $kovHdrInsert =
"INSERT INTO $kovHeader (SELECT DISTINCT(kov_nr), vge_nr, corr_naam, ov_datum, vge_adres, ";
    $kovHdrInsert .=
"type, prijs, notaris, tax_waarde, taxateur, tax_datum, bouwkundige, bouw_datum, definitief FROM $kovTable)";
    CRM_Core_DAO::executeQuery( $kovHdrInsert );
    /*
     * read and process all headers
     */
    $headerDAO = CRM_Core_DAO::executeQuery( "SELECT * FROM $kovHeader ");
    while ( $headerDAO->fetch() ) {
        if ( !empty( $headerDAO->kov_nr ) && $headerDAO->kov_nr != 0 ) {
            $kov_nr = (int) $headerDAO->kov_nr;
            $vge_nr = (int) $headerDAO->vge_nr;
            $corr_naam = (string) $headerDAO->corr_naam;
            $vge_adres = (string) $headerDAO->vge_adres;
            $type = setKovType( $headerDAO->type );

            $notaris = CRM_Utils_DgwUtils::upperCaseSplitTxt( $headerDAO->notaris );
            $taxateur = CRM_Utils_DgwUtils::upperCaseSplitTxt( $headerDAO->taxateur );
            $bouwkundige = CRM_Utils_DgwUtils::upperCaseSplitTxt( $headerDAO->bouwkundige );
            if ( is_numeric( $headerDAO->prijs ) ) {
                $prijs = (int) $headerDAO->prijs;
            } else {
                $prijs = 0;
            }
            if ( is_numeric( $headerDAO->tax_waarde ) ) {
                $tax_waarde = (int) $headerDAO->tax_waarde;
            } else {
                $tax_waarde = 0;
            }
            if ( !empty( $headerDAO->ov_datum ) ) {
                $headerDAO->ov_datum = CRM_Utils_DgwUtils::correctNlDate( $headerDAO->ov_datum );
                $ov_datum = date( "Y-m-d", strtotime( $headerDAO->ov_datum ) );
                if ( $ov_datum == "1970-01-01" ) {
                    $ov_datum = "";
                }
            } else {
                $ov_datum = "";
            }
            if ( !empty( $headerDAO->tax_datum ) ) {
                $headerDAO->tax_datum = CRM_Utils_DgwUtils::correctNlDate( $headerDAO->tax_datum );
                $tax_datum = date( "Y-m-d", strtotime( $headerDAO->tax_datum ) );
                if ( $tax_datum == "1970-01-01" ) {
                    $tax_datum = "";
                }
            } else {
                $tax_datum = "";
            }
            if ( !empty( $headerDAO->bouw_datum ) ) {
                $headerDAO->bouw_datum = CRM_Utils_DgwUtils::correctNlDate( $headerDAO->bouw_datum );
                $bouw_datum = date( "Y-m-d", strtotime( $headerDAO->bouw_datum ) );
                if ( $bouw_datum == "1970-01-01" ) {
                    $bouw_datum = "";
                }
            } else {
                $bouw_datum = "";
            }
            if ( trim( $headerDAO->definitief ) == "J" ) {
                $definitief = 1;
            } else {
                $definitief = 0;
            }
            /*
             * check if household needs to be created
             */
            $createHouseHold = true;
            /*
             * retrieve all individuals for koopovereenkomst and place in array
             */
            $kovIndividuals = array( );
            $kovIndQry = "SELECT DISTINCT(pers_nr) FROM $kovTable WHERE kov_nr = $kov_nr";
            $individualDAO = CRM_Core_DAO::executeQuery( $kovIndQry );
            while ( $individualDAO->fetch() ) {
                $apiParams = array(
                    'version'               =>  3,
                    'persoonsnummer_first'  =>  $individualDAO->pers_nr
                );
                $apiIndividual = civicrm_api( 'DgwContact', 'Get', $apiParams );
                if ( isset( $apiIndividual['contact_id'] ) ) {
                    $kovIndividuals[] = $apiIndividual['contact_id'];
                }
                /*
                 * check if contact is active 'hoofdhuurder' somewhere and if so
                 * use that household
                 */
                $activeHouseHold = CRM_Utils_DgwUtils::retrieveActiveHouseHold( $apiIndividual['contact_id'] );
                if ( $activeHouseHold['count'] > 0 ) {
                    $createHouseHold = false;
                    $houseHoldId = $activeHouseHold['household_id'];
                }
                /*
                 * if no householdId yet, check if contact is active 'koopovereenkomst partner'
                 * somewhere and if so, use that household
                 */
                if ( $createHouseHold ) {
                    $activeKoopPartner = CRM_Utils_DgwUtils::retrieveActiveKoopPartner( $apiIndividual['contact_id'] );
                    if ( $activeKoopPartner['count'] > 0 ) {
                        $createHouseHold = false;
                        $houseHoldId = $activeKoopPartner['household_id'];
                    }
                }
                /*
                 * create household if required and put in correction group
                 */
                if ( $createHouseHold ) {

                    /*
                     * use all contact details of first person
                     */
                }
                /*
                 * update or create koopovereenkomst
                 */
            }
        }
    }
}
/**
 * function to send error mail and show error in CiviCRM status
 */
function sendError( $errorTitle, $errorMessage ) {
    $mail_params = array();
    $mail_params['subject'] = trim($errorTitle);
    $mail_params['text'] = trim($errorMessage)." Corrigeer het probleem en laad dan de koopovereenkomsten in CiviCRM middels de menuoptie Beheer/Laden koopovereenkomsten.";
    require_once 'CRM/Utils/DgwUtils.php';
    $toMail = CRM_Utils_DgwUtils::getDgwConfigValue( 'helpdesk mail' );
    $mail_params['toEmail'] = $toMail;
    $mail_params['toName'] = "Helpdesk";
    $mail_params['from'] = "CiviCRM";
    CRM_Utils_Mail::send($mail_params);
    CRM_Core_Session::setStatus( $errorMessage, $errorTitle, 'error');
}
/**
 * function to set the type of KOV
 * @author Erik Hommel (erik.hommel@civicoop.org)
 * @param $inputType - type from First Noa
 * @return $outputType in CiviCRM
 */
function setKovType( $inputType ) {
    $outputType = "fout";
    if (!empty($inputType)) {
        $inputType = strtolower($inputType);
        switch($inputType) {
            case "koopgarant extern":
                    $outputType = 1;
                    break;
            case "koopgarant zittende huurders":
                    $outputType = 2;
                    break;
            case "koopplus extern":
                    $outputType = 3;
                    break;
            case "koopplus zittende huurders":
                    $outputType = 4;
                    break;
            case "reguliere verkoop":
                    $outputType = 5;
                    break;
        }
    }
    return $outputType;
}
