<?php
/*
+--------------------------------------------------------------------+
| Added PHP script dgwDelPers.php                                    |
+--------------------------------------------------------------------+
| Project       :   Implementation at De Goede Woning                |
| Author        :   Erik Hommel (EE-atWork, hommel@ee-atwork.nl      |
|                   http://www.ee-atwork.nl)                         |
| Date          :   30 oktober 2012                                  |
| Description   :   Script doet een soft-delete voor personen die    |
|                   een einddatum in First hebben gekregen           |
+--------------------------------------------------------------------+
* */
@date_default_timezone_set( 'Europe/Amsterdam' );
ini_set( 'display_errors', '1' );
set_time_limit(0);
require_once("dgwConfig.php");
/*
 * checkt of er een bestand met nieuwe eindpersonen uit First op de server 
 * staat. Als dat het geval is, worden deze eerst geimporteerd. Als het niet
 * het geval is, meteen door naar verwerken van bestaande records die vorige
 * keer niet verwerkt waren
 */
$bestand = KOVPATH."delPers.csv";
if ( file_exists( $bestand ) ) {
    $importResult = importDelPers( $bestand );
    if ( isset( $importResult['is_error'] ) && $importResult['is_error'] == 1 ) {
        if ( isset( $importResult['error_message'] ) ) {
            sendError( "warning", $importResult['error_message'] );
        }
    } else {
        /*
         * verwerken records richting CiviCRM als er geen fout is
         */
        $processResult = processDelPers( );
        sendError( "info", "Verwerking beëindigde personen uit First succesvol afgerond" );
    }
}
/*
 * Importeer gegevens uit csv-bestand in MySQL tabel
 * @params $bestand - naam van het csv bestand
 * @returns array @result
 *  - is_error = 1 als fout, 0 als geen fout
 *  - error_message = foutboodschap, alleen als fout
 */
function importDelPers( $bestand ) {
    $oudBestand = KOVPATH."delPersOud.csv";
    $result = array( );
    /*
     * connect met database waarin csv bestand omgezet moet worden
     */
    $dbDelPers = mysqli_init( );
    mysqli_options($dbDelPers, MYSQLI_OPT_LOCAL_INFILE, true);
    mysqli_real_connect( $dbDelPers, DGWLOGHOST, DGWLOGUSER, DGWLOGWW, KOVDB );
    if (mysqli_connect_errno( ) ) {
        $result['is_error'] = 1;
        $result['error_message'] = "Laden van csv bestand $bestand is niet gelukt, er kon geen verbinding gemaakt worden met de database ".KOVDB;
        return $result;
    }
    /*
     * leegmaken doeltabel
     */
    if ( $dbDelPers->query( "TRUNCATE TABLE del_pers") === false ) {
        $result['is_error'] = 1;
        $result['error_message'] = "Laden van csv bestand $bestand is niet gelukt, kon tabel del_pers niet leegmaken met TRUNCATE";
        return $result;
    }
    /*
     * gegevens kopiëren naar tussenbestand
     */
    $qryDelPers = 
"LOAD DATA LOCAL INFILE '".$bestand. "' INTO TABLE ".KOVDB.".del_pers FIELDS TERMINATED BY ';'";
    if ( $dbDelPers->query( $qryDelPers ) === false ) {
        $result['is_error'] = 1;
        $result['error_message'] = "Laden van csv bestand $bestand is niet gelukt, kon data niet laden met $qryDelPers. Foutmelding van mysql ".$dbDelPers->error;
        return $result;
    }
    /*
     * check of dgw_del_pers bestaat
     */
    $checkTableExists = checkTableExists( "dgw_del_pers");
    if ( !$checkTableExists ) {
        $result['is_error'] = 1;
        $result['error_message'] = "Tabel dgw_del_pers is niet gevonden!";
        return $result;
    }
    /*
     * alle records uit del_pers lezen en toevoegen aan dgw_del_pers op CiviCRM database
     */
    $qryPers = 
"SELECT * FROM del_pers";
    $resultPers = $dbDelPers->query( $qryPers );
    if ( !$resultPers ) {
        $result['is_error'] = 1;
        $result['error_message'] = "Laden van records uit tabel del_pers met $qryPers niet gelukt, fout uit mysql ".$dbDelPers->error;
        return $result;
    }
    while ( $pers = $resultPers->fetch_array( MYSQLI_ASSOC ) ) {
        /*
         * alleen verwerken als persoon niet al bestaat in dgw_del_pers
         */
        $qryCheck = 
"SELECT COUNT(*) AS aantal FROM dgw_del_pers WHERE persNr = {$pers['persNr']}";
        $daoCheck = CRM_Core_DAO::executeQuery( $qryCheck );
        if ( $daoCheck->fetch( ) ) {
            if ( $daoCheck->aantal == 0 ) {
                $insExec = false;
                $insArray = array( );
                if ( isset( $pers['persNr'] ) && !empty( $pers['persNr'] ) ) {
                    $insExec = true;
                    $insArray[] = "persNr = {$pers['persNr']}";
                }
                if ( isset( $pers['achterNaam'] ) || isset( $pers['voorNaam'] ) ) {
                    $insExec = true;
                    if ( isset( $pers['achterNaam'] ) && !empty( $pers['achterNaam'] ) ) {
                        $naam = CRM_Core_DAO::escapeString( $pers['achterNaam'] );
                    }
                    if ( isset( $pers['voorNaam'] ) && !empty( $pers['voorNaam'] ) ) {
                        if ( empty( $naam ) ) {
                            $naam = CRM_Core_DAO::escapeString( $pers['voorNaam'] );
                        } else {
                            $naam .= ", ".CRM_Core_DAO::escapeString( $pers['voorNaam'] );
                        }
                    }
                    $insArray[] = "naam = '$naam'";
                }
                if ( isset( $pers['geslacht'] ) && !empty( $pers['geslacht'] ) ) {
                    $geslacht = CRM_Core_DAO::escapeString( $pers['geslacht'] );
                    $insExec = true;
                    $insArray[] = "geslacht = '$geslacht'";
                }
                if ( isset( $pers['adres'] ) && !empty( $pers['adres'] ) ) {
                    $adres = CRM_Core_DAO::escapeString( $pers['adres'] );
                    $insExec = true;
                    $insArray[] = "adres = '$adres'";
                }
                if ( isset( $pers['gebDatum'] ) && !empty( $pers['gebDatum'] ) ) {
                    $insExec = true;
                    if ( $pers['gebDatum'] != "31-12-1899" ) {
                        $gebDatum = date("Ymd", strtotime( $pers['gebDatum'] ) );
                        $insArray[] = "gebDatum = '$gebDatum'";
                    }
                }
                $insPers = 
"INSERT INTO dgw_del_pers SET ".implode( ", ", $insArray );
                CRM_Core_DAO::executeQuery( $insPers );
            }
        }
    }
    /*
     * verwijder bestand del_pers_oud.csv als bestaat
     */
    if ( file_exists( $oudBestand ) ) {
        unlink( $oudBestand );
    }
    /*
     * bewaar import bestand als del_pers_oud.csv
     */
    rename( $bestand, $oudBestand );
    $result['is_error'] = 0;
    return $result;
}
/*
 * Stuur email met foutmelding, waarschuwing of info
 * @params $type, $message
 * @returns none
 */
function sendError( $type, $message ) {
    require_once("CRM/Utils/Mail.php");
    require_once("CRM/Utils/String.php");
    $mail_params = array();
    $mail_params['subject'] = trim( strtoupper( $type ) )." van verwerken beëindigde personen uit First";
    $mail_params['text'] = trim( $message)." Corrigeer het probleem en probeer dan opnieuw.";
    $mail_params['toEmail'] = KOVMAIL;
    $mail_params['toName'] = "Helpdesk";
    $mail_params['from'] = "CiviCRM";
    CRM_Utils_Mail::send($mail_params); 
}

/*
 * Verwerk personen uit dgw_del_pers richting CiviCRM
 * @params none
 * @returns none
 */
function processDelPers( ) {
    /*
     * lees alle peronen uit dgw_del_pers
     */
    $qryPers = 
"SELECT persNr, geslacht FROM dgw_del_pers";
    $delPersIds = array( );
    $daoPers = CRM_Core_DAO::executeQuery( $qryPers );
    $arrayPers = array( );
    while ( $daoPers->fetch() ) {
       $arrayPers[] = $daoPers->persNr;
    }
    foreach ( $arrayPers as $persoon ) {
        /*
         * check of het persoonsnummer First voorkomt in CiviCRM als
         * persoon of als organisatie
         */
        require_once 'api/v2/Dgwcontact.php';
        $apiParams = array('persoonsnummer_first' => $persoon );
        $apiContact = civicrm_dgwcontact_get( $apiParams );
        if ( isset( $apiContact[0]['record_count'] ) ) {
            if ( $apiContact[0]['record_count'] != 0 ) {
                if ( isset( $apiContact[1] ) ) {
                    /*
                     * als het contact al verwijderd is in CiviCRM,
                     * verdere verwerking negeren en record uit dgw_del_pers verwijderen
                     */
                    if ( isset( $apiContact[1]['is_deleted'] ) && $apiContact[1]['is_deleted'] == 1 ) {
                        $delPersIds[] = $persoon; 
                    } else {
                    /*
                         * check of contact verwijderd moet worden
                         */
                        $deleteContact = checkDeleteContact( $apiContact[1]['contact_id'], 
                                $apiContact[1]['contact_type'], $persoon );
                        if ( $deleteContact ) {
                            /*
                             * soft delete uit CiviCRM en verwijder uit dgw_del_pers
                             */
                            $apiParams = array( 
                                'contact_id' => $apiContact[1]['contact_id']
                            );
                            $resultDel = civicrm_contact_delete( $apiParams );
                            $delPersIds[] = $persoon; 
                        }
                    }
                }
            } else {
                $updDelPers = 
"UPDATE dgw_del_pers SET type = 'niet in CiviCRM' WHERE persNr = $persoon";
                $updResult = CRM_Core_DAO::executeQuery( $updDelPers );
            }
        } 
    }
    /*
     * Daadwerkelijk verwijderen uit dgw_del_pers van gemarkeerde records
     */
    if ( !empty( $delPersIds ) ) {
        $inPers = implode( ",", $delPersIds );
        $delPers = "DELETE FROM dgw_del_pers WHERE persNr IN ($inPers)";
        CRM_Core_DAO::executeQuery( $delPers );
    }
}
/*
 * check of er nog actieve items voor contact zijn waardoor er niet
 * verwijderd kan worden. Zet vlaggen in database voor geconstateerde
 * afhankelijkheden
 * @params $contactId, $contactType, $persNr
 * @returns true of false
 */
function checkDeleteContact( $contactId, $contactType, $persNr ) {
    /*
     * return false als contactId of persNr leeg
     */
    if ( empty( $contactId ) || empty( $persNr) ) {
        return false;
    }
    $hov = "N";
    $act = "N";
    $dossier = "N";
    $groep = "N";
    $relatie = "N";
    /*
     * check of er huurovereenkomsten zijn voor organisaties. Dit is niet
     * nodig voor personen omdat er dan een relatie hoofd/medehuurder is
     */
    if ( $contactType == "organization" ) {
        $fldHovEndOrg = FLDHOVENDORG;
        $qryCheckHov = 
"SELECT ".FLDHOVENDORG." FROM ".TABHOVORG." WHERE entity_id = $contactId";
        $daoCheckHov = CRM_Core_DAO::executeQuery( $qryCheckHov );
        if ( $daoCheckHov->fetch( ) ) {
            $hov = "J";
        }
    }
    /*
     * check of er nog activiteiten zijn
     */
    require_once 'api/v2/ActivityContact.php';
    $apiParams = array( 'contact_id' => $contactId );
    $apiAct = civicrm_activity_contact_get( $apiParams);
    if ( isset( $apiAct['is_error'] ) && $apiAct['is_error'] == 0 ) {
        $aantal = 0;
        if ( isset( $apiAct['result'] ) && is_array( $apiAct['result'] ) ) {
            foreach ( $apiAct['result'] as $activity ) {
                $aantal++;
            }
        }
        if ( $aantal > 0 ) {
            $act = "J";
        } 
    }
    /*
     * check of er dossiers zijn
     */
    require_once 'api/v2/Case.php';
    $apiCase = civicrm_case_get( $apiParams );
    if ( isset( $apiCase['is_error'] ) && $apiCase['is_error'] == 0 ) {
        $aantal = 0;
        foreach ( $apiCase['result'] as $case ) {
            $aantal++;
        }
        if ( $aantal > 0 ) {
            $dossier = "J";
        }
    }
    /*
     * check of contact nog actief lid van groepen is
     */
    require_once 'api/v2/GroupContact.php';
    $apiGroup = civicrm_group_contact_get( $apiParams );
    if ( isset( $apiGroup['is_error'] ) && $apiGroup['is_error'] == 0 ) {
        $aantal = 0;
        foreach( $apiGroup['result'] as $group ) {
            $aantal++;
        }
        if ( $aantal > 0 ) {
            $groep = "J";
        }
    }    
    /*
     * check of er relaties zijn voor contact. Daarbij gaan we er van
     * uit dat het geen kwaad kan als er GEEN relaties hoofdhuurder of
     * medehuurder zijn en als alle overige relaties beëindigd zijn. Dat geldt
     * alleen maar als er geen activiteiten of dossiers van dit contact zijn!
     */
    $aantalRelaties = 0;
    require_once 'api/v2/Relationship.php';
    $apiRelations = civicrm_relationship_get( $apiParams );
    if ( isset( $apiRelations['is_error'] ) && $apiRelations['is_error'] == 0 ) {
        /*
         * als er al een act of dossier is, mag relatie gelijk aangezet. Er
         * zal dan in elk geval een dossiermanager of hoofhuurder zijn.
         */
        if ( $hov == "J" || $dossier == "J" ) {
            $relatie = "J";
        } else {
            /*
             * relaties doorlopen
             */
            foreach ( $apiRelations['result'] as $relatieItem ) {
                $aantalRelaties++;
                /*
                 * als er een actieve relatie is, relatie gelijk aanzetten
                 */
                if ( isset( $relatieItem['end_date'] ) && empty($relatieItem['end_date']) ) {
                    $relatie = "J";
                } else {
                    /*
                     * als einddatum gevuld, check of einddatum voor vandaag is
                     */
                    if ( isset( $relatieItem['end_date'] ) ) {
                        $toDay = date("Ymd", strtotime( 'now' ) );
                        $endDate = date("Ymd", strtotime( $relatieItem['end_date'] ) );
                        if ( $endDate >= $toDay ) {
                            $relatie = "J";
                        } else {
                            /*
                             * als beëindigde relatie en relatietype = hoofd-
                             * of medehuurder, relatie op J
                             */
                            if ( isset( $relatieItem['relationship_type_id'] ) ) {
                                if ( $relatieItem['relationship_type_id'] == RELHFD ||
                                        $relatieItem['relationship_type_id'] == RELMEDE ) {
                                    $relatie = "J";
                                }
                            }
                        }
                    }
                }
            }
        }                
    }
    /*
     * als er relaties zijn, maar relatie toch op "N" staat, dan is dat omdat
     * er beëindigde relaties zijn die verwijderd mogen worden. In dat geval die
     * relaties ook daadwerkelijk verwijderen
     */
    if ( $relatie == "N" && $aantalRelaties > 0 ) {
        $delRelaties = 
"DELETE FROM civicrm_relationship WHERE contact_id_a = $contactId OR contact_id_b = $contactId";
        CRM_Core_DAO::executeQuery( $delRelaties );
    }
    /*
     * update del_pers record met status voor rapport
     */
    $updString = 
"UPDATE dgw_del_pers SET type = '$contactType', hov = '$hov', relatie = '$relatie', act = '$act', dossier = '$dossier', groep = '$groep', civi_id = $contactId WHERE persNr = $persNr";
    $updRes = CRM_Core_DAO::executeQuery( $updString );
    /*
     * contact mag verwijderd worden als geen van de indicatoren op "J" staan
     */
    if ( $hov == "N" && $dossier == "N" && $act == "N" && $groep == "N" && $relatie == "N" ) {
        return true;
    } else {
        return false;
    }
}
/*
 * check of tabel bestaat in MySQL database
 * @params $tabel
 * @returns true of false
 */
function checkTableExists( $tabel ) {
    /*
     * return false als tabel leeg
     */
    if ( empty( $tabel ) ) {
        return false;
    }
    /*
     * controleren of de tabel bestaat in de database
     */
    $qrySchema = 
"SELECT * FROM information_schema.tables WHERE table_schema = '".DGWCIVIDB."' AND table_name = '$tabel'";
    $resultSchema = CRM_Core_DAO::executeQuery( $qrySchema );
    if ( $resultSchema->fetch() ) {
        return true;
    } else {
        return false;
    }
}